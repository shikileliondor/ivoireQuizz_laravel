<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'level_id' => $this->level_id,
            'level_title' => $this->whenLoaded('level', fn () => $this->level->title),
            'chapter_name' => $this->whenLoaded('level', fn () => $this->level->chapter?->name),
            'category_id' => $this->category_id,
            'category_name' => $this->whenLoaded('category', fn () => $this->category?->name),
            'question_text' => $this->question_text,
            'type' => $this->type,
            'difficulty' => $this->difficulty,
            'image' => $this->image,
            'audio' => $this->audio,
            'explanation' => $this->explanation,
            'points' => (int) $this->points,
            'xp_reward' => (int) $this->xp_reward,
            'time_limit' => (int) $this->time_limit,
            'is_active' => (bool) $this->is_active,
            'answers' => AdminAnswerResource::collection($this->whenLoaded('answers')),
            'pending_reports_count' => $this->whenCounted('pendingReports'),

            // Balancing signal: a question nobody gets right is usually badly
            // worded rather than genuinely hard.
            'stats' => $this->when(isset($this->times_answered), fn () => [
                'times_answered' => (int) $this->times_answered,
                'success_rate' => (int) $this->times_answered > 0
                    ? round(((int) $this->times_correct / (int) $this->times_answered) * 100, 1)
                    : null,
            ]),

            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
