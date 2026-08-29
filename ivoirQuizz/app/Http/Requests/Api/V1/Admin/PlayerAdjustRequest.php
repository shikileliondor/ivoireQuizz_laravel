<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Support actions: unblocking a player stuck by a bug, or granting back what a
 * broken question cost them.
 */
class PlayerAdjustRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lives' => ['sometimes', 'integer', 'min:0', 'max:5'],
            'coins' => ['sometimes', 'integer', 'min:0'],
            'gems' => ['sometimes', 'integer', 'min:0'],
            'role' => ['sometimes', Rule::in(['player', 'admin'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // Removing your own admin rights locks you out of the back office
            // with no way back in through the API.
            $target = $this->route('player');

            if ($this->input('role') === 'player' && $target?->id === $this->user()?->id) {
                $validator->errors()->add('role', 'Vous ne pouvez pas retirer vos propres droits administrateur.');
            }
        });
    }
}
