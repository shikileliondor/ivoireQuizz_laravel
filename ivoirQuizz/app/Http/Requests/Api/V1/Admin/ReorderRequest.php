<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\ApiRequest;
use Illuminate\Validation\Validator;

/**
 * Drag-and-drop reordering in the back office: the panel sends the whole
 * ordered list of ids so the server never has to guess the intended sequence.
 */
class ReorderRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $ids = $this->input('ids', []);

            if (is_array($ids) && count($ids) !== count(array_unique($ids))) {
                $validator->errors()->add('ids', 'La liste contient des doublons.');
            }
        });
    }

    /** @return list<int> */
    public function orderedIds(): array
    {
        return array_map('intval', $this->validated('ids'));
    }

    public function attributes(): array
    {
        return ['ids' => 'liste ordonnée'];
    }
}
