<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserCollectibleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'unlocked_at'=>$this->unlocked_at?->toISOString(),'collectible'=>new CollectibleResource($this->whenLoaded('collectible'))];
    }
}
