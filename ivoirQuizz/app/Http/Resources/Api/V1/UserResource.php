<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'name'=>$this->name,'email'=>$this->email,'phone'=>$this->phone ?? null,'avatar'=>$this->avatar,'xp_total'=>(int)$this->xp_total,'current_level'=>(int)$this->current_level,'coins'=>(int)$this->coins,'gems'=>(int)$this->gems,'total_score'=>(int)$this->total_score,'games_played'=>(int)$this->games_played,'games_won'=>(int)$this->games_won];
    }
}
