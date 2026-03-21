<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreGameSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['nullable', 'exists:categories,id'],
            'mode' => ['required', 'in:category,mixed'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'answers' => ['required', 'array', 'size:10'],
            'answers.*.question_id' => ['required', 'exists:questions,id'],
            'answers.*.selected_option_id' => ['nullable', 'exists:options,id'],
            'answers.*.response_time_seconds' => ['required', 'integer', 'min:0', 'max:20'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('mode') === 'category' && ! $this->filled('category_id')) {
                $validator->errors()->add('category_id', 'Le champ category_id est obligatoire en mode category.');
            }
        });
    }
}
