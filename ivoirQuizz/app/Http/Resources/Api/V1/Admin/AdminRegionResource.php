<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminRegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'intro_title' => $this->intro_title,
            'intro_text' => $this->intro_text,
            'image' => $this->image,
            'map_image' => $this->map_image,
            'order' => (int) $this->order,
            'required_xp' => (int) $this->required_xp,
            'is_active' => (bool) $this->is_active,
            'chapters_count' => $this->whenCounted('chapters'),
            'levels_count' => $this->whenCounted('levels'),
            'chapters' => AdminChapterResource::collection($this->whenLoaded('chapters')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
