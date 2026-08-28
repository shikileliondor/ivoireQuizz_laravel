<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_register_ok(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Azziz', 'username' => 'azziz', 'email' => 'azziz@test.com', 'phone' => '0700000000',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.username', 'azziz')
            ->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);
    }

    public function test_login_ok(): void
    {
        User::factory()->create(['email' => 'azziz@test.com', 'password' => bcrypt('password123'), 'friend_code' => 'ABC123']);
        $this->postJson('/api/v1/auth/login', ['email' => 'azziz@test.com', 'password' => 'password123'])
            ->assertOk()->assertJsonPath('success', true)->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_protected_route_without_token_is_refused(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('success', false);
    }

    public function test_invalid_credentials_return_generic_unauthorized_response(): void
    {
        User::factory()->create(['email' => 'known@example.com']);

        $known = $this->postJson('/api/v1/auth/login', [
            'email' => 'known@example.com',
            'password' => 'wrong-password',
        ]);
        $unknown = $this->postJson('/api/v1/auth/login', [
            'email' => 'unknown@example.com',
            'password' => 'wrong-password',
        ]);

        $known->assertUnauthorized()->assertJsonPath('message', 'Identifiants invalides.');
        $unknown->assertUnauthorized()->assertJsonPath('message', $known->json('message'));
    }

    public function test_suspended_account_cannot_login(): void
    {
        User::factory()->create([
            'email' => 'suspended@example.com',
            'password' => Hash::make('password123'),
            'status' => 'suspended',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'suspended@example.com',
            'password' => 'password123',
        ])->assertForbidden()->assertJsonPath('success', false);
    }

    public function test_logout_all_revokes_every_token(): void
    {
        $user = User::factory()->create();
        $plainToken = $user->createToken('current')->plainTextToken;
        $user->createToken('other');

        $this->withToken($plainToken)->postJson('/api/v1/auth/logout-all')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
