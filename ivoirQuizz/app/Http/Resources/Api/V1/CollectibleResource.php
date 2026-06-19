<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectibleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'type'=>$this->type,'name'=>$this->name,'slug'=>$this->slug,'description'=>$this->description,'image'=>$this->image,'rarity'=>$this->rarity,'region_id'=>$this->region_id,'city_id'=>$this->city_id,'is_unlocked'=>(bool)($this->unlocked ?? false)];
    }
}
