<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CollectibleRequest extends FormRequest
{
    public const TYPES = ['personality', 'monument'];

    public const RARITIES = ['common', 'rare', 'epic', 'legendary'];

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
            'type' => ['required', Rule::in(self::TYPES)],
            'name' => ['required', 'string', 'max:191'],
            'slug' => ['required', 'string', 'max:191', Rule::unique('collectibles')->ignore($this->route('collectible'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'rarity' => ['required', Rule::in(self::RARITIES)],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nom', 'rarity' => 'rareté', 'region_id' => 'région', 'city_id' => 'ville'];
    }
}
