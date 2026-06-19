<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserStreakResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['current_streak'=>(int)$this->current_streak,'longest_streak'=>(int)$this->longest_streak,'last_played_date'=>$this->last_played_date?->toDateString(),'streak_freezes'=>(int)$this->streak_freezes];
    }
}
