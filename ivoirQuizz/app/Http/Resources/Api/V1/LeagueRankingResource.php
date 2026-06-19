<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeagueRankingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['user'=>['id'=>$this->user?->id ?? $this->user_id,'name'=>$this->user?->name,'avatar'=>$this->user?->avatar],'xp_earned'=>(int)$this->xp_earned,'rank'=>(int)$this->rank];
    }
}
