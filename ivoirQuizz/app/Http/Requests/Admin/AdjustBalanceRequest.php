<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class AdjustBalanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'currency' => ['required', 'in:coin,gem'],
            'amount' => ['required', 'integer', 'between:-100000,100000', 'not_in:0'],
            'reason' => ['required', 'string', 'max:191'],
            'notify' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'currency' => 'monnaie',
            'amount' => 'montant',
            'reason' => 'motif',
        ];
    }
}
