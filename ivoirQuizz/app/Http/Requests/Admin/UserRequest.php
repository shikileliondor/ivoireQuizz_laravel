<?php

namespace App\Http\Requests\Admin;

use App\Support\Pseudo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name' => ['required', 'string', 'max:100'],
            'pseudo' => ['sometimes', 'required', ...Pseudo::rules()],
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', Rule::unique('users')->ignore($userId)],
            'email' => ['required', 'string', 'email', 'max:191', Rule::unique('users')->ignore($userId)],
            'phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return Pseudo::messages();
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'pseudo' => 'pseudo',
            'username' => 'nom d’utilisateur',
            'phone' => 'téléphone',
            'city' => 'ville',
        ];
    }
}
