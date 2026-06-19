<?php
namespace App\Http\Requests\Api\V1\Game;
use App\Http\Requests\Api\V1\ApiRequest;
class FinishGameSessionRequest extends ApiRequest { public function authorize(): bool { return true; } public function rules(): array { return ['score'=>['prohibited'],'xp'=>['prohibited'],'xp_earned'=>['prohibited'],'coins'=>['prohibited'],'coins_earned'=>['prohibited'],'gems'=>['prohibited'],'gems_earned'=>['prohibited'],'accuracy'=>['prohibited']]; } }
