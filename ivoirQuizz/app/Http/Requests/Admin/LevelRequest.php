<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LevelRequest extends FormRequest
{
    public const DIFFICULTIES = ['easy', 'medium', 'hard', 'expert'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('title', '')),
            'is_boss' => $this->boolean('is_boss'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'title' => ['required', 'string', 'max:191'],
            'slug' => [
                'required', 'string', 'max:191',
                Rule::unique('levels')->where('city_id', $this->input('city_id'))->ignore($this->route('level')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'difficulty' => ['required', Rule::in(self::DIFFICULTIES)],
            'order' => ['required', 'integer', 'min:0', 'max:10000'],
            'required_xp' => ['required', 'integer', 'min:0'],
            'questions_count' => ['required', 'integer', 'min:1', 'max:100'],
            'passing_score' => ['required', 'integer', 'min:0', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0', 'max:100000'],
            'coins_reward' => ['required', 'integer', 'min:0', 'max:100000'],
            'gems_reward' => ['required', 'integer', 'min:0', 'max:100000'],
            'is_boss' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'city_id' => 'ville',
            'title' => 'titre',
            'difficulty' => 'difficulté',
            'order' => 'ordre',
            'required_xp' => 'XP requis',
            'questions_count' => 'nombre de questions',
            'passing_score' => 'score de réussite',
            'xp_reward' => 'récompense XP',
            'coins_reward' => 'récompense pièces',
            'gems_reward' => 'récompense gemmes',
        ];
    }
}
