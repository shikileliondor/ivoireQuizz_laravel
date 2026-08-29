<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class ChapterRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $chapter = $this->route('chapter');
        $required = $this->isMethod('POST') ? 'required' : 'sometimes';
        $regionId = $this->input('region_id', $chapter?->region_id);

        return [
            'region_id' => [$required, 'integer', 'exists:regions,id'],
            'name' => [$required, 'string', 'max:255'],
            'slug' => [
                'sometimes', 'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('chapters', 'slug')
                    ->where(fn ($q) => $q->where('region_id', $regionId))
                    ->ignore($chapter?->id),
            ],
            'description' => ['sometimes', 'nullable', 'string'],
            'image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'region_id' => 'région',
            'name' => 'nom',
            'slug' => 'identifiant',
            'order' => 'ordre',
        ];
    }
}
