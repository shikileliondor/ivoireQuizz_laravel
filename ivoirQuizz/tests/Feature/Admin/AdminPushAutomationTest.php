<?php

namespace Tests\Feature\Admin;

use App\Models\DeviceToken;
use App\Models\PushAutomation;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\Notifications\PushCatalog;
use App\Services\Notifications\ReminderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\Concerns\FakesFcm;
use Tests\TestCase;

class AdminPushAutomationTest extends TestCase
{
    use FakesFcm, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->setUpFakeFcm();

        $this->admin = User::factory()->create(['is_admin' => true, 'name' => 'Admin Test']);
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        $this->tearDownFakeFcm();
        parent::tearDown();
    }

    public function test_pages_list_every_type_and_scheduler_state(): void
    {
        $this->get(route('admin.automations.index'))
            ->assertOk()
            ->assertSee('Rappel du matin')
            ->assertSee('Chaque jour à 8 h')
            ->assertSee('Résultat de défi')
            ->assertSee('Jamais lancé')
            ->assertSee(base_path('artisan').' schedule:run', false);

        app(ReminderService::class)->tick();
        $this->get(route('admin.automations.index'))->assertSee('Actif : dernier passage');

        foreach (array_keys(PushCatalog::all()) as $key) {
            $this->get(route('admin.automations.edit', $key))->assertOk()->assertSee(PushCatalog::get($key)['label']);
        }
        $this->get(route('admin.automations.edit', 'inconnu'))->assertNotFound();
    }

    public function test_admin_edits_settings_and_texts(): void
    {
        $templates = collect(PushCatalog::get('morning')['segments'])
            ->map(fn (array $segment) => ['title' => $segment['title'], 'bodies' => implode("\n", $segment['bodies'])])
            ->all();
        $templates['daily'] = ['title' => 'Salut {prenom} !', 'bodies' => "Premier texte\n\n  Deuxième texte  \n"];

        $this->put(route('admin.automations.update', 'morning'), [
            'enabled' => '1',
            'settings' => ['hour' => 9, 'discovery_days' => 3, 'missed_after' => 8, 'missed_every' => 3, 'dormant_after' => 31, 'dormant_every' => 7, 'stop_after' => 90, 'question_of_day' => '0'],
            'templates' => $templates,
        ])->assertRedirect(route('admin.automations.index'));

        $morning = PushAutomation::for('morning');
        $this->assertSame(9, $morning->setting('hour'));
        $this->assertFalse($morning->setting('question_of_day'));
        $this->assertSame(['title' => 'Salut {prenom} !', 'bodies' => ['Premier texte', 'Deuxième texte']], $morning->template('daily'));
        $this->assertSame(['Salut Koffi !', 'Deuxième texte'], $morning->render('daily', ['prenom' => 'Koffi'], 1));

        $this->put(route('admin.automations.update', 'morning'), [
            'settings' => ['hour' => 25],
            'templates' => [],
        ])->assertSessionHasErrors(['settings.hour', 'templates.daily.title']);
    }

    public function test_global_rules_are_saved(): void
    {
        $this->put(route('admin.automations.settings'), ['max_per_day' => 3, 'quiet_start' => 22, 'quiet_end' => 6])
            ->assertSessionHas('success');

        $this->assertSame(['max_per_day' => 3, 'quiet_start' => 22, 'quiet_end' => 6], PushAutomation::globals());
    }

    public function test_test_send_reaches_only_the_admin_devices_with_sample_values(): void
    {
        DeviceToken::query()->create(['user_id' => $this->admin->id, 'token' => 'admin-phone']);
        $this->playerWithDevice('player');

        $this->post(route('admin.automations.test', 'challenge_result'), ['segment' => 'lose'])
            ->assertSessionHas('success', 'Test envoyé : reçu sur 1 appareil(s) sur 1.');

        Http::assertSentCount(1);
        $this->assertSame(
            ['title' => 'Défaite… 😅', 'body' => 'Awa a joué ton défi et gagne 60 à 80. Prends ta revanche !'],
            $this->messageFor('admin-phone')['notification'],
        );
        $this->assertDatabaseCount('push_logs', 0);
    }

    public function test_scheduled_campaign_leaves_at_its_time_through_the_scheduler(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 10:00'));
        $this->playerWithDevice('player');

        $this->post(route('admin.notifications.store'), [
            'audience' => 'all', 'title' => 'Grand jeu', 'body' => 'Ce soir à 20 h !',
            'when' => 'later', 'send_at' => '2026-11-05T18:00',
        ])->assertSessionHas('success', 'Notification programmée pour le 05/11/2026 à 18:00.');

        Http::assertNothingSent();
        $this->travelTo(Carbon::parse('2026-11-05 17:59'));
        app(ReminderService::class)->tick();
        Http::assertNothingSent();

        $this->travelTo(Carbon::parse('2026-11-05 18:00'));
        app(ReminderService::class)->tick();

        $this->assertSame('Grand jeu', $this->messageFor('player')['notification']['title']);
        $this->assertDatabaseHas('push_notifications', ['title' => 'Grand jeu', 'status' => 'sent', 'sent_count' => 1, 'batches_done' => 1]);
    }

    public function test_canceled_campaign_never_leaves(): void
    {
        $this->travelTo(Carbon::parse('2026-11-05 10:00'));
        $this->playerWithDevice('player');
        $this->post(route('admin.notifications.store'), [
            'audience' => 'all', 'title' => 'Annulée', 'body' => 'Texte', 'when' => 'later', 'send_at' => '2026-11-05T12:00',
        ]);
        $notification = PushNotification::query()->sole();

        $this->patch(route('admin.notifications.cancel', $notification))->assertSessionHas('success');
        $this->travelTo(Carbon::parse('2026-11-05 12:30'));
        app(ReminderService::class)->tick();

        Http::assertNothingSent();
        $this->assertSame('canceled', $notification->fresh()->status);
        $this->patch(route('admin.notifications.cancel', $notification))->assertSessionHas('error');
    }

    public function test_schedule_date_must_be_in_the_future(): void
    {
        $this->playerWithDevice('player');

        $this->post(route('admin.notifications.store'), [
            'audience' => 'all', 'title' => 'Titre', 'body' => 'Texte', 'when' => 'later', 'send_at' => now()->subHour()->format('Y-m-d\TH:i'),
        ])->assertSessionHasErrors('send_at');

        $this->assertDatabaseCount('push_notifications', 0);
    }
}
