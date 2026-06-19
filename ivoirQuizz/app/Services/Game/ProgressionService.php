<?php

namespace App\Services\Game;

use App\Models\City;
use App\Models\Level;
use App\Models\Region;
use App\Models\User;
use App\Models\UserCityProgress;
use App\Models\UserLevelProgress;
use App\Models\UserRegionProgress;
use Illuminate\Support\Facades\DB;

class ProgressionService
{
    public function initializeForUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $region = Region::query()->where('is_active', true)->orderBy('order')->first();
            if (! $region) { return; }
            $city = $region->cities()->where('is_active', true)->orderBy('order')->first();
            $level = $city?->levels()->where('is_active', true)->orderBy('order')->first();
            UserRegionProgress::query()->firstOrCreate(['user_id' => $user->id, 'region_id' => $region->id], ['is_unlocked' => true]);
            if ($city) { UserCityProgress::query()->firstOrCreate(['user_id' => $user->id, 'city_id' => $city->id], ['is_unlocked' => true]); }
            if ($level) { UserLevelProgress::query()->firstOrCreate(['user_id' => $user->id, 'level_id' => $level->id], ['is_unlocked' => true]); }
        });
    }

    public function isLevelUnlocked(User $user, Level $level): bool
    {
        $this->initializeForUser($user);
        return UserLevelProgress::query()->where('user_id', $user->id)->where('level_id', $level->id)->where('is_unlocked', true)->exists();
    }

    public function completeLevel(User $user, Level $level, int $score, float $accuracy): void
    {
        DB::transaction(function () use ($user, $level, $score, $accuracy): void {
            $stars = $this->stars($level, $accuracy);
            $progress = UserLevelProgress::query()->firstOrNew(['user_id' => $user->id, 'level_id' => $level->id]);
            $progress->fill([
                'best_score' => max((int) $progress->best_score, $score),
                'best_accuracy' => max((float) $progress->best_accuracy, $accuracy),
                'stars' => max((int) $progress->stars, $stars),
                'attempts' => ((int) $progress->attempts) + 1,
                'is_unlocked' => true,
                'is_completed' => $stars > 0 || $progress->is_completed,
                'completed_at' => $stars > 0 ? now() : $progress->completed_at,
            ])->save();
            $this->recalculateParents($user, $level);
        });
    }

    public function unlockNextAfterLevel(User $user, Level $level): void
    {
        DB::transaction(function () use ($user, $level): void {
            $next = Level::query()->where('city_id', $level->city_id)->where('is_active', true)->where('order', '>', $level->order)->orderBy('order')->first();
            if (! $next) {
                $city = $level->city;
                $nextCity = City::query()->where('region_id', $city->region_id)->where('is_active', true)->where('order', '>', $city->order)->orderBy('order')->first();
                if ($nextCity) {
                    UserCityProgress::query()->firstOrCreate(['user_id' => $user->id, 'city_id' => $nextCity->id], ['is_unlocked' => true]);
                    $next = $nextCity->levels()->where('is_active', true)->orderBy('order')->first();
                }
            }
            if ($next) { UserLevelProgress::query()->firstOrCreate(['user_id' => $user->id, 'level_id' => $next->id], ['is_unlocked' => true]); }
        });
    }

    public function completeRegionIfBoss(User $user, Level $level): void
    {
        if (! $level->is_boss) { return; }
        DB::transaction(function () use ($user, $level): void {
            $region = $level->city->region;
            UserRegionProgress::query()->updateOrCreate(['user_id' => $user->id, 'region_id' => $region->id], ['is_unlocked' => true, 'is_completed' => true, 'progress_percent' => 100, 'completed_at' => now()]);
            $nextRegion = Region::query()->where('is_active', true)->where('order', '>', $region->order)->orderBy('order')->first();
            if ($nextRegion) {
                UserRegionProgress::query()->firstOrCreate(['user_id' => $user->id, 'region_id' => $nextRegion->id], ['is_unlocked' => true]);
                $city = $nextRegion->cities()->where('is_active', true)->orderBy('order')->first();
                if ($city) { UserCityProgress::query()->firstOrCreate(['user_id' => $user->id, 'city_id' => $city->id], ['is_unlocked' => true]); }
                $first = $city?->levels()->where('is_active', true)->orderBy('order')->first();
                if ($first) { UserLevelProgress::query()->firstOrCreate(['user_id' => $user->id, 'level_id' => $first->id], ['is_unlocked' => true]); }
            }
        });
    }

    private function stars(Level $level, float $accuracy): int
    { return $accuracy >= 90 ? 3 : ($accuracy >= 75 ? 2 : ($accuracy >= $level->passing_score ? 1 : 0)); }

    private function recalculateParents(User $user, Level $level): void
    {
        $city = $level->city; $region = $city->region;
        $cityLevelIds = $city->levels()->pluck('id');
        $completedCity = UserLevelProgress::query()->where('user_id', $user->id)->whereIn('level_id', $cityLevelIds)->where('is_completed', true)->count();
        UserCityProgress::query()->updateOrCreate(['user_id' => $user->id, 'city_id' => $city->id], ['is_unlocked' => true, 'progress_percent' => $cityLevelIds->count() ? round($completedCity / $cityLevelIds->count() * 100, 2) : 0, 'is_completed' => $cityLevelIds->count() === $completedCity, 'completed_at' => $cityLevelIds->count() === $completedCity ? now() : null]);
        $regionLevelIds = $region->levels()->pluck('levels.id');
        $completedRegion = UserLevelProgress::query()->where('user_id', $user->id)->whereIn('level_id', $regionLevelIds)->where('is_completed', true)->count();
        UserRegionProgress::query()->updateOrCreate(['user_id' => $user->id, 'region_id' => $region->id], ['is_unlocked' => true, 'progress_percent' => $regionLevelIds->count() ? round($completedRegion / $regionLevelIds->count() * 100, 2) : 0]);
    }
}
