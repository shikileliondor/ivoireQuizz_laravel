<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('username')) {
            $this->merge(['username' => mb_strtolower(trim((string) $this->input('username')))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'username' => ['sometimes', 'required', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', Rule::unique('users')->ignore($this->user()?->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'avatar' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'email' => ['prohibited'],
            'xp' => ['prohibited'],
            'xp_total' => ['prohibited'],
            'level' => ['prohibited'],
            'current_level' => ['prohibited'],
            'coins' => ['prohibited'],
            'status' => ['prohibited'],
        ];
    }
}
