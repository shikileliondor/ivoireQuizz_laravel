<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\GameConstants;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'level_id'=>$this->level_id,'mode'=>$this->mode,'status'=>$this->status,'score'=>(int)$this->score,'total_questions'=>(int)$this->total_questions,'seconds_per_question'=>$this->seconds_per_question,'helpers'=>$this->helpers(),'started_at'=>$this->started_at?->toISOString(),'finished_at'=>$this->finished_at?->toISOString(),'questions'=>QuestionResource::collection($this->whenLoaded('questions')),'answers'=>GameSessionAnswerResource::collection($this->whenLoaded('gameSessionAnswers'))];
    }

    /**
     * Réglages figés au démarrage (clés historiques conservées), complétés du format lu par l'app,
     * identique à GET /quiz/questions : hint/skip {available, price}, pause_on_hint, pause_seconds.
     */
    private function helpers(): ?array
    {
        $config = $this->gameplay_config;
        if (! is_array($config)) {
            return $config;
        }

        $chrono = $this->mode === GameConstants::MODE_CHRONO;
        // Un indice n'existe que s'il est rédigé : pas de bouton actif si aucune question du tirage n'en a.
        $drawHasHint = ! $this->relationLoaded('questions') || $this->questions->contains(fn ($question): bool => filled($question->hint));

        return $config + [
            'hint' => ['available' => (bool) ($config['hint_enabled'] ?? false) && $drawHasHint, 'price' => (int) ($config['hint_cost'] ?? 0)],
            'skip' => ['available' => (bool) ($config['skip_enabled'] ?? false), 'price' => (int) ($config['skip_cost'] ?? 0)],
            'pause_on_hint' => $chrono && (bool) ($config['chrono_pause_on_hint'] ?? false),
            'pause_seconds' => $chrono ? (int) ($config['chrono_hint_pause_seconds'] ?? 0) : 0,
        ];
    }
}
