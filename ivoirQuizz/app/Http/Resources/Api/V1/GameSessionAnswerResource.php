<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSessionAnswerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['id'=>$this->id,'question_id'=>$this->question_id,'answer_id'=>$this->answer_id,'is_correct'=>(bool)$this->is_correct,'response_time'=>(int)$this->response_time,'points_earned'=>(int)$this->points_earned,'xp_earned'=>(int)$this->xp_earned];
    }
}
