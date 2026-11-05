<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'level_id'=>$this->level_id,'category_id'=>$this->category_id,'question'=>$this->question_text ?? $this->text ?? $this->title ?? null,'type'=>$this->type ?? null,'image_url'=>$this->imageUrl(),'difficulty'=>$this->difficulty,'points'=>(int)$this->points,'xp_reward'=>(int)$this->xp_reward,'time_limit'=>(int)$this->time_limit,'has_hint'=>filled($this->hint),'explanation'=>$this->when(isset($this->show_explanation) && $this->show_explanation, $this->explanation),'answers'=>AnswerResource::collection($this->whenLoaded('answers'))];
    }
}
