<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'name'=>$this->name,'slug'=>$this->slug,'description'=>$this->description,'order'=>(int)$this->order,'required_xp'=>(int)$this->required_xp,'is_unlocked'=>(bool)($this->progress?->is_unlocked ?? false),'is_completed'=>(bool)($this->progress?->is_completed ?? false),'stars'=>(int)($this->progress?->stars ?? 0),'progress_percent'=>(float)($this->progress?->progress_percent ?? 0),'cities'=>CityResource::collection($this->whenLoaded('cities'))];
    }
}
