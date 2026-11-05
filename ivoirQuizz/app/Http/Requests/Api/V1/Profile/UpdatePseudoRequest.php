<?php

namespace App\Http\Requests\Api\V1\Profile;

use App\Http\Requests\Api\V1\ApiRequest;
use App\Support\Pseudo;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdatePseudoRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'pseudo' => ['required', ...Pseudo::rules()],
        ];
    }

    public function messages(): array
    {
        return Pseudo::messages();
    }

    /** Un seul champ : le message précis remplace le message générique, l'app peut l'afficher tel quel. */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => Pseudo::MESSAGE,
            'errors' => $validator->errors(),
        ], 422));
    }
}
