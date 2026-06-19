<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserPassportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'completed_at'=>$this->completed_at?->toISOString(),'region'=>new RegionResource($this->whenLoaded('region'))];
    }
}
