<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class DeleteAccountRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'confirmation' => ['required', 'in:SUPPRIMER'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! Hash::check((string) $this->input('current_password'), (string) $this->user()?->password)) {
                $validator->errors()->add('current_password', 'Le mot de passe actuel est incorrect.');
            }
        }];
    }
}
