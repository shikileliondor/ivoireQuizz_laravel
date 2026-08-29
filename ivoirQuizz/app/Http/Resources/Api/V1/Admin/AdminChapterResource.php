<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminChapterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'region_id' => $this->region_id,
            'region_name' => $this->whenLoaded('region', fn () => $this->region->name),
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'order' => (int) $this->order,
            'is_active' => (bool) $this->is_active,
            'levels_count' => $this->whenCounted('levels'),
            'levels' => AdminLevelResource::collection($this->whenLoaded('levels')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
