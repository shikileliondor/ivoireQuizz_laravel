<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'level_id'=>$this->level_id,'mode'=>$this->mode,'status'=>$this->status,'score'=>(int)$this->score,'total_questions'=>(int)$this->total_questions,'started_at'=>$this->started_at?->toISOString(),'finished_at'=>$this->finished_at?->toISOString(),'questions'=>QuestionResource::collection($this->whenLoaded('questions')),'answers'=>GameSessionAnswerResource::collection($this->whenLoaded('gameSessionAnswers'))];
    }
}
