<?php

namespace Tests\Feature\Site;

use App\Enums\UserStatus;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /** @return array<string, array{string, string}> */
    public static function pages(): array
    {
        return [
            'accueil' => ['/', 'Voyagez à travers la Côte'],
            'confidentialité' => ['/confidentialite', 'Politique de confidentialité'],
            'cookies' => ['/cookies', 'Politique cookies'],
            'conditions' => ['/conditions-utilisation', 'Conditions d&#039;utilisation'],
            'mentions légales' => ['/mentions-legales', 'Mentions légales'],
            'suppression de compte' => ['/suppression-compte', 'Supprimer votre compte'],
        ];
    }

    #[DataProvider('pages')]
    public function test_public_pages_are_reachable_without_login(string $uri, string $heading): void
    {
        $this->get($uri)
            ->assertOk()
            ->assertSee($heading, false)
            ->assertSee(route('site.privacy'), false)
            ->assertSee(route('site.account-deletion'), false);
    }

    public function test_legal_pages_show_the_configured_publisher_and_contact(): void
    {
        config([
            'site.publisher.name' => 'Studio Exemple',
            'site.publisher.address' => 'Cocody, Abidjan',
            'site.contact_email' => 'contact@exemple.ci',
        ]);

        $this->get('/confidentialite')
            ->assertSee('Studio Exemple')
            ->assertSee('Cocody, Abidjan')
            ->assertSee('mailto:contact@exemple.ci', false);

        $this->get('/mentions-legales')->assertSee('Studio Exemple')->assertSee('contact@exemple.ci');
    }

    public function test_play_store_button_links_to_the_store_once_configured(): void
    {
        $this->get('/')->assertSee('Bientôt disponible sur');

        config(['site.play_store_url' => 'https://play.google.com/store/apps/details?id=ci.exemple.quiz']);

        $this->get('/')
            ->assertDontSee('Bientôt disponible sur')
            ->assertSee('https://play.google.com/store/apps/details?id=ci.exemple.quiz', false);
    }

    public function test_a_player_can_delete_their_account_from_the_website(): void
    {
        $user = User::factory()->create(['email' => 'joueur@example.com', 'phone' => '0700000000', 'city' => 'Bouaké']);
        $user->createToken('mobile');
        DeviceToken::query()->create(['user_id' => $user->id, 'token' => 'fcm-token', 'platform' => 'android']);

        $this->from('/suppression-compte')
            ->post('/suppression-compte', [
                'email' => ' Joueur@Example.com ',
                'password' => 'password',
                'confirmation' => 'SUPPRIMER',
            ])
            ->assertRedirect('/suppression-compte')
            ->assertSessionHas('deleted', true);

        $deleted = User::withTrashed()->findOrFail($user->id);
        $this->assertSoftDeleted($deleted);
        $this->assertSame('Compte supprimé', $deleted->name);
        $this->assertNotSame('joueur@example.com', $deleted->email);
        $this->assertNull($deleted->phone);
        $this->assertNull($deleted->city);
        $this->assertSame(UserStatus::Disabled, $deleted->status);
        $this->assertSame(0, $deleted->tokens()->count());
        $this->assertDatabaseMissing('device_tokens', ['token' => 'fcm-token']);

        $this->get('/suppression-compte')->assertSee('Votre compte a été supprimé.');
    }

    public function test_a_wrong_password_does_not_delete_the_account(): void
    {
        $user = User::factory()->create(['email' => 'joueur@example.com']);

        $this->from('/suppression-compte')
            ->post('/suppression-compte', [
                'email' => 'joueur@example.com',
                'password' => 'mauvais-mot-de-passe',
                'confirmation' => 'SUPPRIMER',
            ])
            ->assertRedirect('/suppression-compte')
            ->assertSessionHasErrors(['email' => 'E-mail ou mot de passe incorrect.']);

        $this->assertNotSoftDeleted($user);
    }

    public function test_the_confirmation_word_is_required(): void
    {
        $user = User::factory()->create(['email' => 'joueur@example.com']);

        $this->post('/suppression-compte', [
            'email' => 'joueur@example.com',
            'password' => 'password',
            'confirmation' => 'supprimer',
        ])->assertSessionHasErrors('confirmation');

        $this->assertNotSoftDeleted($user);
    }

    public function test_repeated_failures_are_throttled_even_with_the_right_password(): void
    {
        $user = User::factory()->create(['email' => 'joueur@example.com']);

        foreach (range(1, 5) as $attempt) {
            $this->post('/suppression-compte', [
                'email' => 'joueur@example.com',
                'password' => 'essai-'.$attempt,
                'confirmation' => 'SUPPRIMER',
            ]);
        }

        $this->post('/suppression-compte', [
            'email' => 'joueur@example.com',
            'password' => 'password',
            'confirmation' => 'SUPPRIMER',
        ])->assertSessionHasErrors('email');

        $this->assertNotSoftDeleted($user);
    }
}
