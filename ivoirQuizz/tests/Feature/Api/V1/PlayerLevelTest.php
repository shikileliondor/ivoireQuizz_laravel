<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Services\Game\RewardService;
use Tests\TestCase;

class PlayerLevelTest extends TestCase
{
    public function test_level_is_recalculated_from_server_awarded_xp(): void
    {
        $user = User::factory()->create(['xp_total' => 490, 'current_level' => 1]);

        app(RewardService::class)->addXp($user, 20, 'test', 1, 'Test reward');

        $this->assertSame(510, $user->refresh()->xp_total);
        $this->assertSame(2, $user->current_level);
        $this->assertDatabaseHas('reward_transactions', [
            'user_id' => $user->id,
            'type' => 'xp',
            'amount' => 20,
        ]);
    }
}
