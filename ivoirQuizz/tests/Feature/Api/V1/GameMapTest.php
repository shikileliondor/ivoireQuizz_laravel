<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GameMapTest extends TestCase
{
    public function test_game_map_ok(): void
    {
        Sanctum::actingAs(User::factory()->create(['friend_code' => 'MAP001']));
        $this->getJson('/api/v1/game/map')->assertOk()->assertJsonPath('success', true);
    }
}
