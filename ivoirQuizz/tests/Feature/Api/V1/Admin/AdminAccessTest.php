<?php

namespace Tests\Feature\Api\V1\Admin;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    public function test_guests_are_rejected(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
    }

    public function test_a_plain_player_cannot_reach_the_back_office(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'player']));

        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
        $this->getJson('/api/v1/admin/questions')->assertStatus(403);
        $this->postJson('/api/v1/admin/regions', ['name' => 'Pirate'])->assertStatus(403);
    }

    public function test_an_admin_reaches_the_back_office(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['content', 'players', 'gameplay', 'moderation']]);
    }

    public function test_an_admin_cannot_strip_their_own_admin_rights(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/admin/players/{$admin->id}", ['role' => 'player'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);
    }
}
