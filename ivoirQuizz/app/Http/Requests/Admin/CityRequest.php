<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CityRequest extends FormRequest
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
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'name' => ['required', 'string', 'max:191'],
            'slug' => [
                'required', 'string', 'max:191',
                Rule::unique('cities')->where('region_id', $this->input('region_id'))->ignore($this->route('city')),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'order' => ['required', 'integer', 'min:0', 'max:10000'],
            'required_xp' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function attributes(): array
    {
        return ['region_id' => 'région', 'name' => 'nom', 'order' => 'ordre', 'required_xp' => 'XP requis'];
    }
}
