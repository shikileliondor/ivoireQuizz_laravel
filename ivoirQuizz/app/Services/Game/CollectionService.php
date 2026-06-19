<?php

namespace App\Services\Game;

use App\Models\Collectible;
use App\Models\User;
use App\Models\UserCollectible;
use Illuminate\Support\Facades\DB;

class CollectionService
{
    public function unlock(User $user, Collectible $collectible, string $sourceType = null, int $sourceId = null): UserCollectible
    {
        return DB::transaction(fn (): UserCollectible => UserCollectible::query()->firstOrCreate(
            ['user_id' => $user->id, 'collectible_id' => $collectible->id],
            ['source_type' => $sourceType, 'source_id' => $sourceId, 'unlocked_at' => now()]
        ));
    }

    public function unlockRandom(User $user, ?int $regionId = null, ?int $cityId = null, string $sourceType = null, int $sourceId = null): ?UserCollectible
    {
        $owned = UserCollectible::query()->where('user_id', $user->id)->pluck('collectible_id');
        $base = Collectible::query()->where('is_active', true)->whereNotIn('id', $owned);
        $collectible = (clone $base)->when($cityId, fn ($q) => $q->where('city_id', $cityId))->inRandomOrder()->first()
            ?? (clone $base)->when($regionId, fn ($q) => $q->where('region_id', $regionId))->inRandomOrder()->first()
            ?? $base->inRandomOrder()->first();
        return $collectible ? $this->unlock($user, $collectible, $sourceType, $sourceId) : null;
    }
}
