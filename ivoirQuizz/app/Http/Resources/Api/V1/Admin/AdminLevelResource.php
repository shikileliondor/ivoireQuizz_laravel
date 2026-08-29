<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $required = (int) $this->questions_count;
        $available = $this->active_questions_count;

        return [
            'id' => $this->id,
            'chapter_id' => $this->chapter_id,
            'chapter_name' => $this->whenLoaded('chapter', fn () => $this->chapter->name),
            'region_name' => $this->whenLoaded('chapter', fn () => $this->chapter->region?->name),
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'difficulty' => $this->difficulty,
            'node_type' => $this->node_type ?? 'level',
            'order' => (int) $this->order,
            'required_xp' => (int) $this->required_xp,
            'questions_count' => $required,
            'passing_score' => (int) $this->passing_score,
            'xp_reward' => (int) $this->xp_reward,
            'coins_reward' => (int) $this->coins_reward,
            'gems_reward' => (int) $this->gems_reward,
            'is_boss' => (bool) $this->is_boss,
            'is_active' => (bool) $this->is_active,

            // A level that draws more questions than it owns cannot start a
            // session, so the panel surfaces it as a blocking content gap.
            'available_questions' => $this->when($available !== null, fn () => (int) $available),
            'is_playable' => $this->when($available !== null, fn () => (int) $available >= $required),
            'missing_questions' => $this->when($available !== null, fn () => max(0, $required - (int) $available)),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
