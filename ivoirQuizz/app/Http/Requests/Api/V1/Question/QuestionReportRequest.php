<?php

namespace App\Http\Requests\Api\V1\Question;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class QuestionReportRequest extends ApiRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(['wrong_answer', 'typo', 'inappropriate', 'duplicate', 'other'])],
            'message' => ['nullable', 'string', 'max:1000'],
            'score' => ['prohibited'],
            'xp' => ['prohibited'],
            'coins' => ['prohibited'],
            'gems' => ['prohibited'],
            'accuracy' => ['prohibited'],
            'is_correct' => ['prohibited'],
        ];
    }
}
