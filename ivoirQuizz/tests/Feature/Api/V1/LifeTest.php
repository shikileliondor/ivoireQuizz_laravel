<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LifeTest extends TestCase
{
    public function test_lives_ok(): void
    {
        Sanctum::actingAs(User::factory()->create(['friend_code' => 'LIF001']));
        $this->getJson('/api/v1/lives')->assertOk()->assertJsonPath('success', true);
    }
}
