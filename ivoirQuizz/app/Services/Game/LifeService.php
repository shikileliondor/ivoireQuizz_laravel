<?php

namespace App\Services\Game;

use App\Enums\GameConstants;
use App\Models\User;
use App\Models\UserLife;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LifeService
{
    public function getOrCreate(User $user): UserLife
    {
        return UserLife::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['lives' => GameConstants::MAX_LIVES, 'max_lives' => GameConstants::MAX_LIVES]
        );
    }

    public function canPlay(User $user): bool
    {
        return $this->regenerate($user)->lives > 0;
    }

    public function loseLife(User $user, int $amount = 1): UserLife
    {
        return DB::transaction(function () use ($user, $amount): UserLife {
            $life = UserLife::query()->lockForUpdate()->firstOrCreate(
                ['user_id' => $user->id],
                ['lives' => GameConstants::MAX_LIVES, 'max_lives' => GameConstants::MAX_LIVES]
            );
            $life->lives = max(0, $life->lives - max(0, $amount));
            if ($life->lives < $life->max_lives && $life->next_life_at === null) {
                $life->next_life_at = Carbon::now()->addMinutes(GameConstants::LIFE_REGENERATION_MINUTES);
            }
            $life->save();
            return $life;
        });
    }

    public function addLife(User $user, int $amount = 1): UserLife
    {
        return DB::transaction(function () use ($user, $amount): UserLife {
            $life = $this->getOrCreate($user);
            $life->lives = min($life->max_lives, $life->lives + max(0, $amount));
            $life->next_life_at = $life->lives >= $life->max_lives ? null : ($life->next_life_at ?? Carbon::now()->addMinutes(GameConstants::LIFE_REGENERATION_MINUTES));
            $life->save();
            return $life;
        });
    }

    public function regenerate(User $user): UserLife
    {
        return DB::transaction(function () use ($user): UserLife {
            $life = $this->getOrCreate($user);
            $now = Carbon::now();
            while ($life->next_life_at !== null && $life->next_life_at->lte($now) && $life->lives < $life->max_lives) {
                $life->lives++;
                $life->next_life_at = $life->lives >= $life->max_lives ? null : $life->next_life_at->copy()->addMinutes(GameConstants::LIFE_REGENERATION_MINUTES);
            }
            if ($life->lives < $life->max_lives && $life->next_life_at === null) {
                $life->next_life_at = $now->copy()->addMinutes(GameConstants::LIFE_REGENERATION_MINUTES);
            }
            $life->save();
            return $life;
        });
    }
}
