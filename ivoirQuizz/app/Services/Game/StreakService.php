<?php

namespace App\Services\Game;

use App\Models\User;
use App\Models\UserStreak;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StreakService
{
    public function getOrCreate(User $user): UserStreak
    { return UserStreak::query()->firstOrCreate(['user_id' => $user->id]); }

    public function updateAfterGame(User $user): UserStreak
    {
        return DB::transaction(function () use ($user): UserStreak {
            $streak = $this->getOrCreate($user); $today = Carbon::today(); $yesterday = Carbon::yesterday();
            if ($streak->last_played_date?->isSameDay($today)) { return $streak; }
            if ($streak->last_played_date?->isSameDay($yesterday)) { $streak->current_streak++; }
            elseif ($streak->last_played_date && $streak->last_played_date->lt($yesterday) && $streak->streak_freezes > 0) { $streak->streak_freezes--; $streak->current_streak = max(1, $streak->current_streak + 1); }
            else { $streak->current_streak = 1; }
            $streak->longest_streak = max($streak->longest_streak, $streak->current_streak); $streak->last_played_date = $today; $streak->save(); return $streak;
        });
    }
}
