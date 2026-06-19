<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_register_ok(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Azziz', 'email' => 'azziz@test.com', 'phone' => '0700000000',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertCreated()->assertJsonPath('success', true)->assertJsonStructure(['data' => ['user', 'token']]);
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
}
