<?php
namespace App\Http\Requests\Api\V1\Auth;
use App\Http\Requests\Api\V1\ApiRequest;
class LoginRequest extends ApiRequest { public function authorize(): bool { return true; } public function rules(): array { return ['email'=>['required','email'],'password'=>['required','string']]; } }
