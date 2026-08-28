<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', 'unique:users,username'],
            'email' => ['required', 'string', 'email:rfc', 'max:191', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'xp' => ['prohibited'],
            'level' => ['prohibited'],
            'coins' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
