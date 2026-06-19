<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\{CityResource, LevelResource, RegionResource};
use App\Models\{City, Level, Region, UserCityProgress, UserLevelProgress, UserRegionProgress};
use Illuminate\Http\Request;

class GameMapController extends Controller
{
    use ApiResponse;

    private function attach($items, string $type, $user): void
    {
        if ($items->isEmpty()) { return; }
        $cls = ['region' => UserRegionProgress::class, 'city' => UserCityProgress::class, 'level' => UserLevelProgress::class][$type];
        $key = $type.'_id';
        $progress = $cls::where('user_id', $user->id)->whereIn($key, $items->pluck('id'))->get()->keyBy($key);
        $items->each(fn ($item) => $item->setRelation('progress', $progress->get($item->id)));
    }

    public function map(Request $request)
    {
        $regions = Region::with(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('order'), 'cities.levels' => fn ($q) => $q->where('is_active', true)->orderBy('order')])->where('is_active', true)->orderBy('order')->get();
        $this->attach($regions, 'region', $request->user());
        $cities = $regions->pluck('cities')->flatten();
        $levels = $cities->pluck('levels')->flatten();
        $this->attach($cities, 'city', $request->user());
        $this->attach($levels, 'level', $request->user());

        return $this->successResponse(['regions' => RegionResource::collection($regions), 'current_position' => ['region_id' => $request->user()->current_region_id, 'city_id' => $request->user()->current_city_id, 'level_id' => $request->user()->current_game_level_id]], 'Carte du jeu.');
    }

    public function regions(Request $request)
    {
        $regions = Region::where('is_active', true)->orderBy('order')->get();
        $this->attach($regions, 'region', $request->user());
        return $this->successResponse(RegionResource::collection($regions), 'Régions.');
    }

    public function showRegion(Request $request, Region $region)
    {
        $region->load(['cities' => fn ($q) => $q->where('is_active', true)->orderBy('order'), 'cities.levels' => fn ($q) => $q->where('is_active', true)->orderBy('order')]);
        $this->attach(collect([$region]), 'region', $request->user());
        $this->attach($region->cities, 'city', $request->user());
        $this->attach($region->cities->pluck('levels')->flatten(), 'level', $request->user());
        return $this->successResponse(new RegionResource($region), 'Région.');
    }

    public function showCity(Request $request, City $city)
    {
        $city->load(['levels' => fn ($q) => $q->where('is_active', true)->orderBy('order')]);
        $this->attach(collect([$city]), 'city', $request->user());
        $this->attach($city->levels, 'level', $request->user());
        return $this->successResponse(new CityResource($city), 'Ville.');
    }

    public function showLevel(Request $request, Level $level)
    {
        $this->attach(collect([$level]), 'level', $request->user());
        return $this->successResponse(new LevelResource($level), 'Niveau.');
    }
}
