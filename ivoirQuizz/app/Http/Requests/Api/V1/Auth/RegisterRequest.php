<?php
namespace App\Http\Requests\Api\V1\Auth;
use App\Http\Requests\Api\V1\ApiRequest;
class RegisterRequest extends ApiRequest { public function authorize(): bool { return true; } public function rules(): array { return ['name'=>['required','string','max:255'],'email'=>['required','email','unique:users,email'],'phone'=>['nullable','string','max:30'],'password'=>['required','string','min:8','confirmed']]; } }
