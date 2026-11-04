<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class LeagueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->input('slug') ?: $this->input('name', '')),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:191', Rule::unique('leagues')->ignore($this->route('league'))],
            'rank_order' => ['required', 'integer', 'min:1', 'max:1000', Rule::unique('leagues')->ignore($this->route('league'))],
            'icon' => ['nullable', 'string', 'max:191'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nom', 'rank_order' => 'rang', 'icon' => 'icône', 'color' => 'couleur'];
    }
}
