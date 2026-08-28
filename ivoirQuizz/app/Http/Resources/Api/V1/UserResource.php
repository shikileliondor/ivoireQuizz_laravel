<?php

namespace App\Http\Resources\Api\V1;

use App\Services\User\PlayerLevelService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $progress = app(PlayerLevelService::class)->progress($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $this->avatar,
            'city' => $this->city,
            'bio' => $this->bio,
            'level' => $progress['level'],
            'xp' => $progress['current_xp'],
            'coins' => (int) $this->coins,
            'gems' => (int) $this->gems,
            'total_score' => (int) $this->total_score,
            'games_played' => (int) $this->games_played,
            'games_won' => (int) $this->games_won,
            'status' => $this->status?->value ?? (string) $this->status,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'last_activity_date' => $this->last_activity_date?->toDateString(),
            'progression' => $progress,
            // Conservés pendant la migration des clients mobiles existants.
            'current_level' => $progress['level'],
            'xp_total' => $progress['current_xp'],
        ];
    }
}
