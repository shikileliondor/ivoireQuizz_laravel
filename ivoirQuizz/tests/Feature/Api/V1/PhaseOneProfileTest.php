<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PhaseOneProfileTest extends TestCase
{
    public function test_user_can_read_and_update_own_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonStructure(['success', 'message', 'data' => ['level', 'xp', 'progression']]);

        $this->putJson('/api/v1/profile', [
            'name' => 'Awa Koné',
            'username' => 'awa_kone',
            'city' => 'Bouaké',
            'bio' => 'Passionnée de culture ivoirienne.',
        ])->assertOk()
            ->assertJsonPath('data.username', 'awa_kone')
            ->assertJsonPath('data.city', 'Bouaké');

        $this->assertDatabaseHas('users', ['id' => $user->id, 'username' => 'awa_kone']);
    }

    public function test_profile_cannot_change_server_controlled_values(): void
    {
        $user = User::factory()->create(['xp_total' => 100, 'coins' => 10]);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', [
            'xp' => 999999,
            'coins' => 999999,
            'status' => 'active',
        ])->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['xp', 'coins', 'status']]);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'xp_total' => 100, 'coins' => 10]);
    }

    public function test_suspended_user_cannot_use_protected_api(): void
    {
        $user = User::factory()->create(['status' => 'suspended']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/me')->assertForbidden()->assertJsonPath('success', false);
    }
}
