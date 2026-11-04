<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChestRequest extends FormRequest
{
    public const TYPES = ['bronze', 'silver', 'gold'];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $amount = ['required', 'integer', 'min:0', 'max:1000000'];

        return [
            'name' => ['required', 'string', 'max:191'],
            'type' => ['required', Rule::in(self::TYPES)],
            'min_xp' => $amount,
            'max_xp' => [...$amount, 'gte:min_xp'],
            'min_coins' => $amount,
            'max_coins' => [...$amount, 'gte:min_coins'],
            'min_gems' => $amount,
            'max_gems' => [...$amount, 'gte:min_gems'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'min_xp' => 'XP min',
            'max_xp' => 'XP max',
            'min_coins' => 'pièces min',
            'max_coins' => 'pièces max',
            'min_gems' => 'gemmes min',
            'max_gems' => 'gemmes max',
        ];
    }
}
