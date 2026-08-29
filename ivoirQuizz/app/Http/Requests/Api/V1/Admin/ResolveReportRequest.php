<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Rule;

class ResolveReportRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(['reviewed', 'rejected', 'fixed'])],
            'deactivate_question' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['status' => 'statut'];
    }
}
