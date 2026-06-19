<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'question_id'=>$this->question_id,'text'=>$this->answer_text ?? $this->text ?? $this->option_text ?? null,'order'=>(int)$this->order];
    }
}
