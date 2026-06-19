<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question_id' => $this->question_id,
            'reason' => $this->reason,
            'message' => $this->message,
            'status' => $this->status,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
