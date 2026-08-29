<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminPlayerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'friend_code' => $this->friend_code,
            'avatar' => $this->avatar,
            'avatar_id' => (int) $this->avatar_id,
            'xp_total' => (int) $this->xp_total,
            'total_score' => (int) $this->total_score,
            'coins' => (int) $this->coins,
            'gems' => (int) $this->gems,
            'games_played' => (int) $this->games_played,
            'games_won' => (int) $this->games_won,
            'has_seen_intro' => (bool) $this->has_seen_intro,
            'current_region_id' => $this->current_region_id,
            'current_chapter_id' => $this->current_chapter_id,
            'lives' => $this->whenLoaded('userLives', fn () => (int) $this->userLives?->lives),
            'streak' => $this->whenLoaded('userStreaks', fn () => (int) $this->userStreaks?->current_streak),
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
