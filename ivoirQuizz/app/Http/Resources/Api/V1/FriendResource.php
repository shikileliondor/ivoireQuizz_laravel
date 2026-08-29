<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A friend is another player seen from the outside: public profile and score
 * only. Never their email, phone or progression internals.
 */
class FriendResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar,
            'avatar_id' => (int) $this->avatar_id,
            'friend_code' => $this->friend_code,
            'xp_total' => (int) $this->xp_total,
            'total_score' => (int) $this->total_score,
            'current_level' => (int) $this->current_level,
            'games_played' => (int) $this->games_played,
            'games_won' => (int) $this->games_won,
        ];
    }
}
