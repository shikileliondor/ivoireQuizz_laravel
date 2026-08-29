<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class RegionRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $regionId = $this->route('region')?->id;
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';

        return [
            'name' => [$required, 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('regions', 'slug')->ignore($regionId)],
            'description' => ['sometimes', 'nullable', 'string'],
            'intro_title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'intro_text' => ['sometimes', 'nullable', 'string'],
            'image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'map_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'required_xp' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'slug' => 'identifiant',
            'description' => 'description',
            'intro_title' => "titre d'introduction",
            'intro_text' => "texte d'introduction",
            'order' => 'ordre',
        ];
    }
}
