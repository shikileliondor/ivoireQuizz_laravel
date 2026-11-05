<?php

namespace Tests\Feature\Api;

use App\Models\Friendship;
use App\Models\User;
use App\Services\Auth\GoogleAuthService;
use App\Services\Game\ProgressionService;
use App\Support\Pseudo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PseudoTest extends TestCase
{
    private const REGISTER = [
        'email' => 'awa@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ];

    // ---- Inscription (POST /api/auth/register, utilisé par l'app Flutter) ----

    public function test_register_with_pseudo_uses_it_and_defaults_name_to_pseudo(): void
    {
        $this->postJson('/api/auth/register', [...self::REGISTER, 'pseudo' => 'Kôffi_07'])
            ->assertCreated()
            ->assertJsonPath('data.user.pseudo', 'Kôffi_07')
            ->assertJsonPath('data.user.name', 'Kôffi_07');

        $this->assertDatabaseHas('users', ['email' => 'awa@example.com', 'pseudo' => 'Kôffi_07', 'name' => 'Kôffi_07']);
    }

    public function test_register_with_pseudo_and_name_keeps_both(): void
    {
        $this->postJson('/api/auth/register', [...self::REGISTER, 'pseudo' => 'AwaK', 'name' => 'Awa Koné'])
            ->assertCreated()
            ->assertJsonPath('data.user.pseudo', 'AwaK')
            ->assertJsonPath('data.user.name', 'Awa Koné');
    }

    public function test_register_old_format_derives_pseudo_from_first_word_of_name(): void
    {
        $this->postJson('/api/auth/register', [...self::REGISTER, 'name' => 'Jean-Marc Kouassi'])
            ->assertCreated()
            ->assertJsonPath('data.user.pseudo', 'Jean-Marc')
            ->assertJsonPath('data.user.name', 'Jean-Marc Kouassi');
    }

    public function test_register_old_format_cleans_and_truncates_the_derived_pseudo(): void
    {
        $this->postJson('/api/auth/register', [...self::REGISTER, 'name' => "N'Guessan-Abdoulaye-Konan Yao"])
            ->assertCreated()
            ->assertJsonPath('data.user.pseudo', 'NGuessan-Abdoul');
    }

    public function test_register_old_format_falls_back_to_joueur_and_id_when_name_is_too_short(): void
    {
        $response = $this->postJson('/api/auth/register', [...self::REGISTER, 'name' => 'Al Kouamé'])->assertCreated();

        $this->assertSame('Joueur'.$response->json('data.user.id'), $response->json('data.user.pseudo'));
    }

    public function test_register_rejects_an_invalid_pseudo_with_the_french_message(): void
    {
        $this->postJson('/api/auth/register', [...self::REGISTER, 'pseudo' => 'Awa Koné'])
            ->assertStatus(422)
            ->assertJsonPath('errors.pseudo.0', Pseudo::MESSAGE);

        $this->assertDatabaseMissing('users', ['email' => 'awa@example.com']);
    }

    public function test_register_requires_a_pseudo_or_a_name(): void
    {
        $this->postJson('/api/auth/register', self::REGISTER)
            ->assertStatus(422)
            ->assertJsonPath('errors.pseudo.0', Pseudo::MESSAGE);
    }

    public function test_pseudo_is_not_unique(): void
    {
        User::factory()->create(['pseudo' => 'Awa']);

        $this->postJson('/api/auth/register', [...self::REGISTER, 'pseudo' => 'Awa'])->assertCreated();

        $this->assertSame(2, User::query()->where('pseudo', 'Awa')->count());
    }

    public function test_v1_register_accepts_pseudo_and_old_name_only_format(): void
    {
        $this->postJson('/api/v1/auth/register', [...self::REGISTER, 'username' => 'awa_k', 'pseudo' => 'Awa-CI'])
            ->assertCreated()
            ->assertJsonPath('data.user.pseudo', 'Awa-CI')
            ->assertJsonPath('data.user.name', 'Awa-CI');

        $this->postJson('/api/v1/auth/register', [
            ...self::REGISTER, 'email' => 'yao@example.com', 'username' => 'yao_k', 'name' => 'Yao Kouassi',
        ])->assertCreated()->assertJsonPath('data.user.pseudo', 'Yao');
    }

    public function test_login_returns_the_pseudo(): void
    {
        User::factory()->create(['email' => 'awa@example.com', 'password' => bcrypt('password123'), 'pseudo' => 'AwaK']);

        $this->postJson('/api/auth/login', ['email' => 'awa@example.com', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonPath('data.user.pseudo', 'AwaK');
    }

    // ---- Connexion Google (POST /api/v1/auth/google) ----

    public function test_google_sets_pseudo_from_given_name_on_creation_and_never_overwrites_it(): void
    {
        $this->fakeGoogleClaims(['given_name' => 'Aminata', 'name' => 'Aminata Traoré']);

        $created = $this->postJson('/api/v1/auth/google', ['id_token' => 'a.b.c'])
            ->assertOk()
            ->assertJsonPath('data.created', true)
            ->assertJsonPath('data.user.pseudo', 'Aminata');

        // Le joueur choisit un autre pseudo, puis Google renvoie un prénom différent.
        User::query()->findOrFail($created->json('data.user.id'))->forceFill(['pseudo' => 'Mina225'])->save();
        $this->fakeGoogleClaims(['given_name' => 'Ami', 'name' => 'Ami Traoré']);

        $this->postJson('/api/v1/auth/google', ['id_token' => 'a.b.c'])
            ->assertOk()
            ->assertJsonPath('data.created', false)
            ->assertJsonPath('data.user.pseudo', 'Mina225');
    }

    public function test_google_falls_back_to_first_word_of_name_then_joueur_and_id(): void
    {
        $this->fakeGoogleClaims(['name' => 'Yao Kouassi'], sub: 'google-1', email: 'yao@gmail.com');
        $this->postJson('/api/v1/auth/google', ['id_token' => 'a.b.c'])->assertJsonPath('data.user.pseudo', 'Yao');

        $this->fakeGoogleClaims([], sub: 'google-2', email: 'anonyme@gmail.com');
        $response = $this->postJson('/api/v1/auth/google', ['id_token' => 'a.b.c'])->assertOk();
        $this->assertSame('Joueur'.$response->json('data.user.id'), $response->json('data.user.pseudo'));
    }

    public function test_google_does_not_give_a_pseudo_to_an_existing_email_account(): void
    {
        $user = User::factory()->create(['email' => 'aminata@gmail.com', 'pseudo' => 'Minou']);
        $this->fakeGoogleClaims(['given_name' => 'Aminata']);

        $this->postJson('/api/v1/auth/google', ['id_token' => 'a.b.c'])
            ->assertOk()
            ->assertJsonPath('data.created', false)
            ->assertJsonPath('data.user.pseudo', 'Minou');

        $this->assertSame('Minou', $user->fresh()->pseudo);
    }

    // ---- Modification (PUT /api/v1/me) ----

    public function test_update_me_changes_the_pseudo_and_returns_the_get_me_format(): void
    {
        $user = User::factory()->create(['pseudo' => 'Ancien']);
        Sanctum::actingAs($user);

        $updated = $this->putJson('/api/v1/me', ['pseudo' => 'Éloïse-225'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.pseudo', 'Éloïse-225');

        $this->assertSame('Éloïse-225', $user->fresh()->pseudo);

        $me = $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.pseudo', 'Éloïse-225');
        $this->assertSame(array_keys($me->json('data')), array_keys($updated->json('data')));
    }

    #[DataProvider('invalidPseudos')]
    public function test_update_me_rejects_invalid_pseudos(mixed $pseudo): void
    {
        $user = User::factory()->create(['pseudo' => 'Ancien']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/me', ['pseudo' => $pseudo])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', Pseudo::MESSAGE)
            ->assertJsonPath('errors.pseudo.0', Pseudo::MESSAGE);

        $this->assertSame('Ancien', $user->fresh()->pseudo);
    }

    public static function invalidPseudos(): array
    {
        return [
            'trop court' => ['Al'],
            'trop long' => ['Abcdefghijklmnop'],
            'espace' => ['Awa Koné'],
            'caractère spécial' => ['Awa!'],
            'emoji' => ['Awa😀'],
            'vide' => [''],
            'pas une chaîne' => [12345],
        ];
    }

    public function test_update_me_requires_the_pseudo_field_and_authentication(): void
    {
        $this->putJson('/api/v1/me', ['pseudo' => 'Valide'])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->putJson('/api/v1/me', [])->assertStatus(422)->assertJsonPath('errors.pseudo.0', Pseudo::MESSAGE);
    }

    public function test_get_auth_me_returns_the_pseudo(): void
    {
        Sanctum::actingAs(User::factory()->create(['pseudo' => 'AwaK']));

        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.pseudo', 'AwaK');
    }

    // ---- Classements, amis, défis ----

    public function test_leaderboards_include_the_pseudo(): void
    {
        $me = User::factory()->create(['pseudo' => 'Moi225']);
        $friend = User::factory()->create(['pseudo' => 'Ami-07']);
        Friendship::query()->create(['requester_id' => $me->id, 'receiver_id' => $friend->id, 'status' => 'accepted']);
        Sanctum::actingAs($me);

        foreach (['/api/leaderboard/global', '/api/leaderboard/weekly'] as $url) {
            $rows = collect($this->getJson($url)->assertOk()->json('data.leaderboard.data'))->keyBy('id');
            $this->assertSame('Moi225', $rows[$me->id]['pseudo'], $url);
            $this->assertSame('Ami-07', $rows[$friend->id]['pseudo'], $url);
        }

        $rows = collect($this->getJson('/api/leaderboard/friends')->assertOk()->json('data.leaderboard'))->keyBy('id');
        $this->assertSame('Moi225', $rows[$me->id]['pseudo']);
        $this->assertSame('Ami-07', $rows[$friend->id]['pseudo']);
    }

    public function test_friends_and_requests_include_the_pseudo(): void
    {
        $me = User::factory()->create();
        $friend = User::factory()->create(['pseudo' => 'Ami-07']);
        $requester = User::factory()->create(['pseudo' => 'Demandeur']);
        Friendship::query()->create(['requester_id' => $me->id, 'receiver_id' => $friend->id, 'status' => 'accepted']);
        Friendship::query()->create(['requester_id' => $requester->id, 'receiver_id' => $me->id, 'status' => 'pending']);
        Sanctum::actingAs($me);

        $this->getJson('/api/friends')->assertOk()->assertJsonPath('data.friends.0.pseudo', 'Ami-07');
        $this->getJson('/api/friends/requests')->assertOk()->assertJsonPath('data.requests.0.requester.pseudo', 'Demandeur');
    }

    public function test_challenges_include_both_players_pseudos(): void
    {
        $challenger = User::factory()->create(['pseudo' => 'Lanceur']);
        $opponent = User::factory()->create(['pseudo' => 'Adversaire']);
        DB::table('friend_challenges')->insert([
            'code' => 'ABCD1234', 'challenger_id' => $challenger->id, 'opponent_id' => $opponent->id,
            'question_ids' => '[]', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        Sanctum::actingAs($opponent);

        $this->getJson('/api/challenges/ABCD1234')
            ->assertOk()
            ->assertJsonPath('data.challenger.id', $challenger->id)
            ->assertJsonPath('data.challenger.pseudo', 'Lanceur')
            ->assertJsonPath('data.opponent.pseudo', 'Adversaire');

        $this->getJson('/api/challenges')
            ->assertOk()
            ->assertJsonPath('data.challenges.0.challenger_pseudo', 'Lanceur')
            ->assertJsonPath('data.challenges.0.opponent_pseudo', 'Adversaire');
    }

    public function test_home_includes_the_pseudo(): void
    {
        Sanctum::actingAs(User::factory()->create(['pseudo' => 'AwaK']));

        $this->getJson('/api/home')->assertOk()->assertJsonPath('data.user.pseudo', 'AwaK');
    }

    // ---- Remplissage des comptes existants ----

    public function test_migration_backfills_existing_accounts(): void
    {
        $migration = require database_path('migrations/2026_11_05_000006_add_pseudo_to_users_table.php');
        $migration->down();

        $rows = [
            'Jean-Marc Kouassi' => 'Jean-Marc',
            '  Éloïse   Bamba ' => 'Éloïse',
            "N'Guessan-Abdoulaye-Konan" => 'NGuessan-Abdoul',
            'Al Kouamé' => null,
            '!!!' => null,
        ];
        $ids = [];
        foreach (array_keys($rows) as $i => $name) {
            $ids[$name] = DB::table('users')->insertGetId([
                'name' => $name, 'email' => "user{$i}@example.com", 'password' => 'x',
                'friend_code' => "CODE0{$i}", 'status' => 'active',
            ]);
        }
        $deletedId = DB::table('users')->insertGetId([
            'name' => 'Compte supprimé', 'email' => 'deleted@example.invalid', 'password' => 'x',
            'friend_code' => 'DELETE', 'status' => 'disabled', 'deleted_at' => now(),
        ]);

        $migration->up();

        $this->assertTrue(Schema::hasColumn('users', 'pseudo'));
        foreach ($rows as $name => $expected) {
            $this->assertSame(
                $expected ?? 'Joueur'.$ids[$name],
                DB::table('users')->where('id', $ids[$name])->value('pseudo'),
                $name,
            );
        }
        $this->assertNull(DB::table('users')->where('id', $deletedId)->value('pseudo'));
    }

    /** @param array<string, mixed> $claims */
    private function fakeGoogleClaims(array $claims, string $sub = 'google-sub-1', string $email = 'aminata@gmail.com'): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $service = Mockery::mock(GoogleAuthService::class, [app(ProgressionService::class)])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('verifyIdToken')->andReturn((object) [
            'sub' => $sub,
            'email' => $email,
            'email_verified' => true,
            ...$claims,
        ]);

        $this->app->instance(GoogleAuthService::class, $service);
    }
}
