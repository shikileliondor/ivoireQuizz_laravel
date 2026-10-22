<?php

namespace App\Http\Requests\Api;

use App\Models\Question;
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
            'mode' => ['required', 'in:category,mixed,daily'],
            'duration_seconds' => ['required', 'integer', 'min:0'],
            'answers' => ['required', 'array', 'size:10'],
            'answers.*.question_id' => ['required', 'distinct', 'exists:questions,id'],
            'answers.*.selected_option_id' => ['nullable', 'exists:answers,id'],
            'answers.*.response_time_seconds' => ['required', 'integer', 'min:0', 'max:30'],
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
            if ($this->input('mode') === 'category' && $this->filled('category_id')) {
                $ids = collect($this->input('answers', []))->pluck('question_id')->filter()->unique();
                if ($ids->isNotEmpty() && Question::whereIn('id', $ids)
                    ->where(fn ($query) => $query->whereNull('category_id')->orWhere('category_id', '<>', $this->input('category_id')))
                    ->exists()) {
                    $validator->errors()->add('answers', 'Les questions doivent appartenir à la catégorie choisie.');
                }
            }
        });
    }
}
