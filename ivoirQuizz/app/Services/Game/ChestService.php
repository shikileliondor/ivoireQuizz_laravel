<?php

namespace App\Services\Game;

use App\Enums\GameConstants;
use App\Exceptions\Game\ChestAlreadyOpenedException;
use App\Models\Chest;
use App\Models\User;
use App\Models\UserChest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ChestService
{
    public function __construct(private RewardService $rewards, private CollectionService $collections) {}

    public function grantChest(User $user, string $type, string $sourceType = null, int $sourceId = null): UserChest
    {
        $chest = Chest::query()->where('type', $type)->where('is_active', true)->firstOrFail();
        return UserChest::query()->create(['user_id' => $user->id, 'chest_id' => $chest->id, 'source_type' => $sourceType, 'source_id' => $sourceId, 'status' => GameConstants::CHEST_AVAILABLE]);
    }

    public function openChest(User $user, UserChest $userChest): array
    {
        return DB::transaction(function () use ($user, $userChest): array {
            $userChest = UserChest::query()->with('chest')->lockForUpdate()->findOrFail($userChest->id);
            if ($userChest->user_id !== $user->id || $userChest->status === GameConstants::CHEST_OPENED) { Log::warning('Invalid chest open attempt', ['user_id' => $user->id, 'user_chest_id' => $userChest->id]); throw new ChestAlreadyOpenedException('This chest cannot be opened.'); }
            $chest = $userChest->chest;
            $xp = random_int($chest->min_xp, max($chest->min_xp, $chest->max_xp));
            $coins = random_int($chest->min_coins, max($chest->min_coins, $chest->max_coins));
            $gems = random_int($chest->min_gems, max($chest->min_gems, $chest->max_gems));
            if ($xp) { $this->rewards->addXp($user, $xp, 'user_chest', $userChest->id, 'Chest XP reward'); }
            if ($coins) { $this->rewards->addCoins($user, $coins, 'user_chest', $userChest->id, 'Chest coin reward'); }
            if ($gems) { $this->rewards->addGems($user, $gems, 'user_chest', $userChest->id, 'Chest gem reward'); }
            $collectible = random_int(1, 100) <= 25 ? $this->collections->unlockRandom($user, null, null, 'user_chest', $userChest->id) : null;
            $userChest->update(['status' => GameConstants::CHEST_OPENED, 'opened_at' => now()]);
            return ['xp' => $xp, 'coins' => $coins, 'gems' => $gems, 'collectible' => $collectible];
        });
    }
}
