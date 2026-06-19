<?php

namespace App\Services\Game;

use App\Models\Region;
use Illuminate\Support\Facades\Cache;

class GameCacheService
{
    public const REGIONS_MAP_CACHE_KEY = 'game:regions:map';
    public const REGIONS_MAP_CACHE_TTL_SECONDS = 21600;

    public function cacheRegionsMap(): array
    {
        $regions = Region::query()
            ->with(['cities' => function ($query): void {
                $query->where('is_active', true)->orderBy('order');
            }, 'cities.levels' => function ($query): void {
                $query->where('is_active', true)->orderBy('order');
            }])
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(fn (Region $region): array => [
                'id' => $region->id,
                'name' => $region->name,
                'slug' => $region->slug,
                'description' => $region->description,
                'order' => $region->order,
                'required_xp' => $region->required_xp,
                'cities' => $region->cities->map(fn ($city): array => [
                    'id' => $city->id,
                    'region_id' => $city->region_id,
                    'name' => $city->name,
                    'slug' => $city->slug,
                    'description' => $city->description,
                    'order' => $city->order,
                    'required_xp' => $city->required_xp,
                    'levels' => $city->levels->map(fn ($level): array => [
                        'id' => $level->id,
                        'city_id' => $level->city_id,
                        'name' => $level->name,
                        'slug' => $level->slug,
                        'type' => $level->type,
                        'difficulty' => $level->difficulty,
                        'order' => $level->order,
                        'xp_reward' => $level->xp_reward,
                        'coins_reward' => $level->coins_reward,
                        'gems_reward' => $level->gems_reward,
                        'passing_score' => $level->passing_score,
                        'is_boss' => $level->is_boss,
                    ])->values()->all(),
                ])->values()->all(),
            ])
            ->values()
            ->all();

        Cache::put(self::REGIONS_MAP_CACHE_KEY, $regions, self::REGIONS_MAP_CACHE_TTL_SECONDS);

        return $regions;
    }

    public function getRegionsMap(): array
    {
        return Cache::remember(
            self::REGIONS_MAP_CACHE_KEY,
            self::REGIONS_MAP_CACHE_TTL_SECONDS,
            fn (): array => $this->cacheRegionsMap(),
        );
    }

    public function clearRegionsMapCache(): void
    {
        Cache::forget(self::REGIONS_MAP_CACHE_KEY);
    }
}
