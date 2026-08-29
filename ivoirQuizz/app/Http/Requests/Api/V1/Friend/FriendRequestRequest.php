<?php

namespace App\Http\Requests\Api\V1\Friend;

use App\Http\Requests\Api\V1\ApiRequest;

class FriendRequestRequest extends ApiRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'friend_code' => ['required', 'string', 'size:6', 'alpha_num'],
        ];
    }

    public function attributes(): array
    {
        return ['friend_code' => 'code ami'];
    }

    public function messages(): array
    {
        return [
            'friend_code.size' => 'Un code ami contient exactement 6 caractères.',
        ];
    }
}
