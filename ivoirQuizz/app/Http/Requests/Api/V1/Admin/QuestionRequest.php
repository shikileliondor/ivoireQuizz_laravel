<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\GameConstants;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuestionRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'level_id' => [$required, 'integer', 'exists:levels,id'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'question_text' => [$required, 'string', 'max:1000'],
            'type' => ['sometimes', Rule::in(['text', 'image', 'audio'])],
            'difficulty' => ['sometimes', Rule::in([
                GameConstants::DIFFICULTY_EASY,
                GameConstants::DIFFICULTY_MEDIUM,
                GameConstants::DIFFICULTY_HARD,
                GameConstants::DIFFICULTY_EXPERT,
            ])],
            'image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'audio' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'explanation' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'points' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'xp_reward' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'time_limit' => ['sometimes', 'integer', 'min:5', 'max:120'],
            'is_active' => ['sometimes', 'boolean'],

            'answers' => [$required, 'array', 'min:2', 'max:6'],
            'answers.*.id' => ['sometimes', 'nullable', 'integer'],
            'answers.*.answer_text' => ['required', 'string', 'max:500'],
            'answers.*.is_correct' => ['required', 'boolean'],
            'answers.*.order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $answers = $this->input('answers');

            if (is_array($answers)) {
                $correct = collect($answers)->filter(fn ($a) => filter_var($a['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN))->count();

                if ($correct !== 1) {
                    $validator->errors()->add('answers', 'La question doit avoir exactement une bonne réponse.');
                }

                $texts = collect($answers)->pluck('answer_text')->map(fn ($t) => mb_strtolower(trim((string) $t)));

                if ($texts->unique()->count() !== $texts->count()) {
                    $validator->errors()->add('answers', 'Deux réponses identiques ne peuvent pas coexister.');
                }
            }

            // A media question whose media is missing renders as an unanswerable
            // blank in the client, so it must never reach the database.
            $type = $this->input('type', 'text');

            if ($type === 'image' && blank($this->input('image'))) {
                $validator->errors()->add('image', 'Une question de type image doit avoir une image.');
            }

            if ($type === 'audio' && blank($this->input('audio'))) {
                $validator->errors()->add('audio', 'Une question de type audio doit avoir un fichier audio.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'level_id' => 'niveau',
            'category_id' => 'catégorie',
            'question_text' => 'énoncé',
            'explanation' => 'explication',
            'answers' => 'réponses',
            'time_limit' => 'temps limite',
        ];
    }
}
