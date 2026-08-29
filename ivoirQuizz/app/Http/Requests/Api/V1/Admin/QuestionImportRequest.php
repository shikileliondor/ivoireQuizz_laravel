<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\GameConstants;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

/**
 * Bulk entry path: writing 50 questions in a spreadsheet then pasting the rows
 * is far faster than 50 round trips through the single-question form.
 */
class QuestionImportRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'questions' => ['required', 'array', 'min:1', 'max:100'],
            'questions.*.question_text' => ['required', 'string', 'max:1000'],
            'questions.*.category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'questions.*.difficulty' => ['sometimes', Rule::in([
                GameConstants::DIFFICULTY_EASY,
                GameConstants::DIFFICULTY_MEDIUM,
                GameConstants::DIFFICULTY_HARD,
                GameConstants::DIFFICULTY_EXPERT,
            ])],
            'questions.*.explanation' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'questions.*.points' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'questions.*.xp_reward' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'questions.*.time_limit' => ['sometimes', 'integer', 'min:5', 'max:120'],
            'questions.*.answers' => ['required', 'array', 'min:2', 'max:6'],
            'questions.*.answers.*.answer_text' => ['required', 'string', 'max:500'],
            'questions.*.answers.*.is_correct' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['level_id' => 'niveau', 'questions' => 'questions'];
    }
}
