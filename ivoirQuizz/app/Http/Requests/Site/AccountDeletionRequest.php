<?php

namespace App\Http\Requests\Site;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Validator;

class AccountDeletionRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    private ?User $user = null;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:191'],
            'password' => ['required', 'string'],
            'confirmation' => ['required', 'in:SUPPRIMER'],
        ];
    }

    public function messages(): array
    {
        return [
            'confirmation.in' => 'Tapez SUPPRIMER en majuscules pour confirmer.',
        ];
    }

    /**
     * Vérifie l'identité après la validation des champs. Les échecs sont limités
     * par e-mail + IP, comme la connexion, pour empêcher de deviner un mot de passe.
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $key = $this->throttleKey();

                if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
                    $minutes = (int) ceil(RateLimiter::availableIn($key) / 60);
                    $validator->errors()->add('email', "Trop de tentatives. Réessayez dans {$minutes} minute(s).");

                    return;
                }

                $user = User::query()->where('email', $this->normalizedEmail())->first();

                if (! $user || ! Hash::check($this->string('password')->toString(), (string) $user->password)) {
                    RateLimiter::hit($key, 15 * 60);
                    $validator->errors()->add('email', 'E-mail ou mot de passe incorrect.');

                    return;
                }

                RateLimiter::clear($key);
                $this->user = $user;
            },
        ];
    }

    public function authenticatedUser(): User
    {
        return $this->user;
    }

    private function normalizedEmail(): string
    {
        return mb_strtolower(trim($this->string('email')->toString()));
    }

    private function throttleKey(): string
    {
        return 'site-account-deletion|'.$this->normalizedEmail().'|'.$this->ip();
    }
}
