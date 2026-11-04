<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Connecte uniquement un administrateur actif ; les échecs sont limités par email + IP.
     */
    public function authenticate(): void
    {
        if (RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => "Trop de tentatives. Réessayez dans {$seconds} secondes.",
            ]);
        }

        $credentials = [
            'email' => $this->string('email')->trim()->toString(),
            'password' => $this->string('password')->toString(),
            'is_admin' => true,
            'status' => UserStatus::Active->value,
        ];

        if (! Auth::guard('web')->attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => 'Identifiants invalides ou compte non autorisé.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function throttleKey(): string
    {
        return 'admin-login|'.Str::lower($this->string('email')->toString()).'|'.$this->ip();
    }
}
