<?php

namespace App\Services\Game;

use App\Models\City;
use App\Models\Level;
use App\Models\Region;
use App\Models\User;
use App\Models\UserCityProgress;
use App\Models\UserLevelProgress;
use App\Models\UserRegionProgress;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProgressionService
{
    public function initializeForUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $region = Region::query()->where('is_active', true)->orderBy('order')->first();
            if (! $region) {
                return;
            }
            $city = $region->cities()->where('is_active', true)->orderBy('order')->first();
            $level = $city?->levels()->where('is_active', true)->orderBy('order')->first();
            $this->ensureProgressUnlocked(UserRegionProgress::class, ['user_id' => $user->id, 'region_id' => $region->id]);
            if ($city) {
                $this->ensureProgressUnlocked(UserCityProgress::class, ['user_id' => $user->id, 'city_id' => $city->id]);
            }
            if ($level) {
                $this->ensureProgressUnlocked(UserLevelProgress::class, ['user_id' => $user->id, 'level_id' => $level->id]);
            }
        });
    }

    /** @param class-string<Model> $modelClass */
    private function ensureProgressUnlocked(string $modelClass, array $identity): void
    {
        $progress = $modelClass::query()->firstOrCreate($identity, ['is_unlocked' => true]);
        if (! $progress->is_unlocked) {
            $progress->forceFill(['is_unlocked' => true])->save();
        }
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
            if ($next) {
                UserLevelProgress::query()->firstOrCreate(['user_id' => $user->id, 'level_id' => $next->id], ['is_unlocked' => true]);
            }
        });
    }

    public function completeRegionIfBoss(User $user, Level $level): void
    {
        if (! $level->is_boss) {
            return;
        }
        DB::transaction(function () use ($user, $level): void {
            $region = $level->city->region;
            UserRegionProgress::query()->updateOrCreate(['user_id' => $user->id, 'region_id' => $region->id], ['is_unlocked' => true, 'is_completed' => true, 'progress_percent' => 100, 'completed_at' => now()]);
            $nextRegion = Region::query()->where('is_active', true)->where('order', '>', $region->order)->orderBy('order')->first();
            if ($nextRegion) {
                UserRegionProgress::query()->firstOrCreate(['user_id' => $user->id, 'region_id' => $nextRegion->id], ['is_unlocked' => true]);
                $city = $nextRegion->cities()->where('is_active', true)->orderBy('order')->first();
                if ($city) {
                    UserCityProgress::query()->firstOrCreate(['user_id' => $user->id, 'city_id' => $city->id], ['is_unlocked' => true]);
                }
                $first = $city?->levels()->where('is_active', true)->orderBy('order')->first();
                if ($first) {
                    UserLevelProgress::query()->firstOrCreate(['user_id' => $user->id, 'level_id' => $first->id], ['is_unlocked' => true]);
                }
            }
        });
    }

    /** Unlock Aboisso after correct answers to 25 distinct Korhogo campaign questions. */
    public function unlockAboissoIfEligible(User $user): bool
    {
        $correctUniqueQuestions = DB::table('game_session_answers as gsa')
            ->join('game_sessions as gs', 'gs.id', '=', 'gsa.game_session_id')
            ->join('questions as q', 'q.id', '=', 'gsa.question_id')
            ->join('levels as question_levels', 'question_levels.id', '=', 'q.level_id')
            ->join('cities as korhogo', 'korhogo.id', '=', 'question_levels.city_id')
            ->join('regions as poro', 'poro.id', '=', 'korhogo.region_id')
            ->where('gs.user_id', $user->id)
            ->where('gs.status', 'completed')
            ->whereIn('gs.mode', ['level', 'boss'])
            ->whereColumn('gs.level_id', 'q.level_id')
            ->where('gsa.is_correct', true)
            ->where('korhogo.slug', 'korhogo')
            ->where('poro.slug', 'poro')
            ->whereNotNull('q.source_key')
            ->where('q.source_key', 'like', 'korhogo.q.%')
            ->distinct()
            ->count('q.source_key');

        if ($correctUniqueQuestions < 25) {
            return false;
        }

        return DB::transaction(function () use ($user): bool {
            $city = City::query()
                ->where('slug', 'aboisso')
                ->whereHas('region', fn ($query) => $query->where('slug', 'sud-comoe'))
                ->where('is_active', true)
                ->first();

            if (! $city) {
                return false;
            }

            UserRegionProgress::query()->updateOrCreate(
                ['user_id' => $user->id, 'region_id' => $city->region_id],
                ['is_unlocked' => true],
            );
            UserCityProgress::query()->updateOrCreate(
                ['user_id' => $user->id, 'city_id' => $city->id],
                ['is_unlocked' => true],
            );

            $firstLevel = $city->levels()->where('is_active', true)->orderBy('order')->first();
            if ($firstLevel) {
                UserLevelProgress::query()->updateOrCreate(
                    ['user_id' => $user->id, 'level_id' => $firstLevel->id],
                    ['is_unlocked' => true],
                );
            }

            return true;
        });
    }

    private function stars(Level $level, float $accuracy): int
    {
        return $accuracy >= 90 ? 3 : ($accuracy >= 75 ? 2 : ($accuracy >= $level->passing_score ? 1 : 0));
    }

    private function recalculateParents(User $user, Level $level): void
    {
        $city = $level->city;
        $region = $city->region;
        $cityLevelIds = $city->levels()->pluck('id');
        $completedCity = UserLevelProgress::query()->where('user_id', $user->id)->whereIn('level_id', $cityLevelIds)->where('is_completed', true)->count();
        UserCityProgress::query()->updateOrCreate(['user_id' => $user->id, 'city_id' => $city->id], ['is_unlocked' => true, 'progress_percent' => $cityLevelIds->count() ? round($completedCity / $cityLevelIds->count() * 100, 2) : 0, 'is_completed' => $cityLevelIds->count() === $completedCity, 'completed_at' => $cityLevelIds->count() === $completedCity ? now() : null]);
        $regionLevelIds = $region->levels()->pluck('levels.id');
        $completedRegion = UserLevelProgress::query()->where('user_id', $user->id)->whereIn('level_id', $regionLevelIds)->where('is_completed', true)->count();
        UserRegionProgress::query()->updateOrCreate(['user_id' => $user->id, 'region_id' => $region->id], ['is_unlocked' => true, 'progress_percent' => $regionLevelIds->count() ? round($completedRegion / $regionLevelIds->count() * 100, 2) : 0]);
    }
}
