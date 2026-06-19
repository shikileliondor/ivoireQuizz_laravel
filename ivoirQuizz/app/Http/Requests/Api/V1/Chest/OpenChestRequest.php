<?php
namespace App\Http\Requests\Api\V1\Chest;
use App\Http\Requests\Api\V1\ApiRequest;
class OpenChestRequest extends ApiRequest { public function authorize(): bool { return $this->user()?->id === $this->route('userChest')?->user_id; } public function rules(): array { return []; } }
