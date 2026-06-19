<?php
namespace App\Http\Requests\Api\V1\Game;
use App\Http\Requests\Api\V1\ApiRequest;
class StartGameSessionRequest extends ApiRequest { public function authorize(): bool { return true; } public function rules(): array { return ['mode'=>['nullable','in:level,boss,daily_challenge,mixed']]; } }
