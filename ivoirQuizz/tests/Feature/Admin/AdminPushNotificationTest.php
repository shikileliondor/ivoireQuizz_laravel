<?php

namespace Tests\Feature\Admin;

use App\Models\DeviceToken;
use App\Models\PushNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private string $credentialsPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Clé de compte de service factice : le jeton Google est préchargé, aucune signature n'est faite.
        $this->credentialsPath = tempnam(sys_get_temp_dir(), 'fcm');
        file_put_contents($this->credentialsPath, json_encode([
            'project_id' => 'notifquizz-test',
            'client_email' => 'fcm@notifquizz-test.iam.gserviceaccount.com',
            'private_key' => 'fake-key',
        ]));
        config(['services.fcm.credentials' => $this->credentialsPath]);
        Cache::put('fcm_access_token', 'google-access-token', 3300);

        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
    }

    protected function tearDown(): void
    {
        @unlink($this->credentialsPath);
        parent::tearDown();
    }

    public function test_broadcast_reaches_active_players_and_forgets_uninstalled_apps(): void
    {
        $alice = $this->playerWithDevice('token-alice');
        $this->playerWithDevice('token-bob', ['status' => 'active'], 'ios');
        $this->playerWithDevice('token-gone');
        $this->playerWithDevice('token-suspended', ['status' => 'suspended']);

        Http::fake(function (Request $request) {
            return $request['message']['token'] === 'token-gone'
                ? Http::response(['error' => ['details' => [['errorCode' => 'UNREGISTERED']]]], 404)
                : Http::response(['name' => 'projects/notifquizz-test/messages/1']);
        });

        $this->post(route('admin.notifications.store'), [
            'audience' => 'all',
            'title' => 'Nouveau défi',
            'body' => 'Viens jouer à Korhogo !',
        ])->assertRedirect(route('admin.notifications.index'))
            ->assertSessionHas('success', 'Notification envoyée à 3 appareil(s) : 2 reçue(s), 1 appareil(s) obsolète(s) retiré(s).');

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://fcm.googleapis.com/v1/projects/notifquizz-test/messages:send'
            && $request->hasHeader('Authorization', 'Bearer google-access-token')
            && $request['message']['notification'] === ['title' => 'Nouveau défi', 'body' => 'Viens jouer à Korhogo !']
            && $request['message']['data'] === ['type' => 'admin_broadcast']);
        Http::assertNotSent(fn (Request $request) => $request['message']['token'] === 'token-suspended');

        $this->assertDatabaseMissing('device_tokens', ['token' => 'token-gone']);
        $this->assertDatabaseHas('device_tokens', ['token' => 'token-alice', 'user_id' => $alice->id]);
        $this->assertDatabaseHas('push_notifications', [
            'admin_id' => $this->admin->id,
            'audience' => 'all',
            'devices_count' => 3,
            'sent_count' => 2,
            'failed_count' => 0,
            'removed_count' => 1,
        ]);
    }

    public function test_inactive_audience_targets_players_who_stopped_playing(): void
    {
        $this->playerWithDevice('token-today', ['last_activity_date' => today()]);
        $this->playerWithDevice('token-old', ['last_activity_date' => today()->subDays(20)]);
        $this->playerWithDevice('token-never', ['last_activity_date' => null]);
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'ok'])]);

        $this->post(route('admin.notifications.store'), [
            'audience' => 'inactive',
            'days' => 14,
            'title' => 'Tu nous manques',
            'body' => 'Reviens jouer !',
        ])->assertSessionHasNoErrors();

        Http::assertSentCount(2);
        Http::assertNotSent(fn (Request $request) => $request['message']['token'] === 'token-today');
        $this->assertSame(['days' => 14], PushNotification::query()->sole()->filters);
    }

    public function test_single_player_is_found_by_friend_code_and_platform_filter_applies(): void
    {
        $player = $this->playerWithDevice('token-android', ['friend_code' => 'ABC123']);
        DeviceToken::query()->create(['user_id' => $player->id, 'token' => 'token-ios', 'platform' => 'ios']);
        $this->playerWithDevice('token-other');
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'ok'])]);

        $this->post(route('admin.notifications.store'), [
            'audience' => 'user',
            'user' => 'abc123',
            'platform' => 'ios',
            'title' => 'Bravo',
            'body' => 'Tu es premier !',
        ])->assertSessionHasNoErrors();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['message']['token'] === 'token-ios');
    }

    public function test_unknown_player_is_a_validation_error(): void
    {
        Http::fake();

        $this->post(route('admin.notifications.store'), [
            'audience' => 'user',
            'user' => 'personne@ivoire.test',
            'title' => 'Bravo',
            'body' => 'Message',
        ])->assertSessionHasErrors('user');

        Http::assertNothingSent();
    }

    public function test_test_send_targets_only_the_admin_devices(): void
    {
        DeviceToken::query()->create(['user_id' => $this->admin->id, 'token' => 'token-admin']);
        $this->playerWithDevice('token-player');
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'ok'])]);

        $this->post(route('admin.notifications.store'), ['audience' => 'me', 'title' => 'Test', 'body' => 'Test'])
            ->assertSessionHas('success');

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) => $request['message']['token'] === 'token-admin');
    }

    public function test_nothing_is_sent_or_recorded_without_matching_devices(): void
    {
        Http::fake();

        $this->post(route('admin.notifications.store'), ['audience' => 'me', 'title' => 'Test', 'body' => 'Test'])
            ->assertSessionHas('error');

        Http::assertNothingSent();
        $this->assertDatabaseCount('push_notifications', 0);
    }

    public function test_firebase_configuration_errors_are_shown_to_the_admin(): void
    {
        $this->playerWithDevice('token-alice');
        config(['services.fcm.credentials' => 'storage/app/firebase/missing.json']);
        Http::fake();

        $this->post(route('admin.notifications.store'), ['audience' => 'all', 'title' => 'Test', 'body' => 'Test'])
            ->assertSessionHas('error', fn (string $message) => str_starts_with($message, 'Envoi impossible : FCM : clé du compte de service introuvable'));

        Http::assertNothingSent();
        $this->assertDatabaseCount('push_notifications', 0);
    }

    public function test_page_shows_reachable_devices_and_history(): void
    {
        $this->playerWithDevice('token-alice');
        PushNotification::query()->create([
            'admin_id' => $this->admin->id, 'title' => 'Ancien envoi', 'body' => 'Corps', 'audience' => 'inactive',
            'filters' => ['days' => 30], 'devices_count' => 5, 'sent_count' => 4, 'failed_count' => 1,
        ]);

        $this->get(route('admin.notifications.index', ['user' => 'alice@ivoire.test']))
            ->assertOk()
            ->assertSee('Ancien envoi')
            ->assertSee('Joueurs inactifs')
            ->assertSee('alice@ivoire.test');
    }

    private function playerWithDevice(string $token, array $attributes = [], string $platform = 'android'): User
    {
        $player = User::factory()->create($attributes);
        DeviceToken::query()->create(['user_id' => $player->id, 'token' => $token, 'platform' => $platform]);

        return $player;
    }
}
