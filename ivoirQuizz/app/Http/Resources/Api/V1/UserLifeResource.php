<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserLifeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['lives'=>(int)$this->lives,'max_lives'=>(int)$this->max_lives,'next_life_at'=>$this->next_life_at?->toISOString(),'can_play'=>$this->lives > 0];
    }
}
