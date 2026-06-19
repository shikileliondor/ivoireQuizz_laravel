<?php
namespace App\Http\Requests\Api\V1\Game;
use App\Http\Requests\Api\V1\ApiRequest;
class SubmitAnswerRequest extends ApiRequest { public function authorize(): bool { return true; } public function rules(): array { return ['question_id'=>['required','exists:questions,id'],'answer_id'=>['nullable','exists:answers,id'],'response_time'=>['required','integer','min:0','max:300']]; } }
