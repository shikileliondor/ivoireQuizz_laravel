<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class QuestionRequest extends FormRequest
{
    public const TYPES = ['text', 'image', 'audio'];

    public const MAX_ANSWERS = 6;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Les lignes de réponse laissées vides sont ignorées ; les clés sont conservées
     * pour que le bouton radio « correct » désigne toujours la bonne ligne.
     */
    protected function prepareForValidation(): void
    {
        $answers = collect($this->input('answers', []))
            ->filter(fn ($answer): bool => is_array($answer) && trim((string) ($answer['text'] ?? '')) !== '')
            ->all();

        $this->merge([
            'answers' => $answers,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'level_id' => ['required', 'integer', 'exists:levels,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'question_text' => ['required', 'string', 'max:1000'],
            'type' => ['required', Rule::in(self::TYPES)],
            'difficulty' => ['required', Rule::in(LevelRequest::DIFFICULTIES)],
            'explanation' => ['nullable', 'string', 'max:2000'],
            'points' => ['required', 'integer', 'min:0', 'max:1000'],
            'xp_reward' => ['required', 'integer', 'min:0', 'max:1000'],
            'time_limit' => ['required', 'integer', 'min:5', 'max:300'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
            'answers' => ['required', 'array', 'min:2', 'max:'.self::MAX_ANSWERS],
            'answers.*.id' => ['nullable', 'integer'],
            'answers.*.text' => ['required', 'string', 'max:255', 'distinct:ignore_case'],
            'correct' => ['required'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! array_key_exists((string) $this->input('correct'), $this->input('answers', []))) {
                    $validator->errors()->add('correct', 'Choisissez la bonne réponse parmi les réponses remplies.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'level_id' => 'niveau',
            'category_id' => 'catégorie',
            'question_text' => 'question',
            'difficulty' => 'difficulté',
            'time_limit' => 'temps limite',
            'answers' => 'réponses',
            'answers.*.text' => 'réponse',
            'correct' => 'bonne réponse',
        ];
    }
}
