<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Enums\GameConstants;
use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class LevelRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $level = $this->route('level');
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';
        $chapterId = $this->input('chapter_id', $level?->chapter_id);

        return [
            'chapter_id' => [$required, 'integer', 'exists:chapters,id'],
            'title' => [$required, 'string', 'max:255'],
            'slug' => [
                'sometimes', 'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('levels', 'slug')
                    ->where(fn ($q) => $q->where('chapter_id', $chapterId))
                    ->ignore($level?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'difficulty' => ['sometimes', Rule::in([
                GameConstants::DIFFICULTY_EASY,
                GameConstants::DIFFICULTY_MEDIUM,
                GameConstants::DIFFICULTY_HARD,
                GameConstants::DIFFICULTY_EXPERT,
            ])],
            'node_type' => ['sometimes', Rule::in(['level', 'chest', 'boss', 'review'])],
            'order' => ['sometimes', 'integer', 'min:0'],
            'required_xp' => ['sometimes', 'integer', 'min:0'],
            'questions_count' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'passing_score' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'xp_reward' => ['sometimes', 'integer', 'min:0'],
            'coins_reward' => ['sometimes', 'integer', 'min:0'],
            'gems_reward' => ['sometimes', 'integer', 'min:0'],
            'is_boss' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * ProgressionService branches on both `node_type` and `is_boss`; letting them
     * disagree would produce a level that unlocks as normal but scores as a boss.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $level = $this->route('level');
            $nodeType = $this->input('node_type', $level?->node_type ?? 'level');
            $isBoss = $this->boolean('is_boss', (bool) ($level?->is_boss ?? false));

            if ($this->has('is_boss') || $this->has('node_type')) {
                if ($nodeType === 'boss' && ! $isBoss) {
                    $validator->errors()->add('is_boss', 'Un niveau de type boss doit avoir is_boss à true.');
                }

                if ($nodeType !== 'boss' && $isBoss) {
                    $validator->errors()->add('node_type', 'Seul un niveau de type boss peut avoir is_boss à true.');
                }
            }
        });
    }

    public function attributes(): array
    {
        return [
            'chapter_id' => 'chapitre',
            'title' => 'titre',
            'difficulty' => 'difficulté',
            'node_type' => 'type de nœud',
            'questions_count' => 'nombre de questions',
            'passing_score' => 'score de réussite',
        ];
    }
}
