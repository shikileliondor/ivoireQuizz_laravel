<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Throwable;

class PasswordResetService
{
    public function sendLink(string $email): string
    {
        try {
            return Password::broker()->sendResetLink(['email' => mb_strtolower($email)]);
        } catch (Throwable $exception) {
            Log::warning('Échec d’envoi du lien de réinitialisation.', [
                'exception' => $exception::class,
            ]);

            return Password::RESET_LINK_SENT;
        }
    }

    /**
     * @param  array{email: string, token: string, password: string}  $credentials
     */
    public function reset(array $credentials): string
    {
        return Password::broker()->reset(
            $credentials,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                $user->tokens()->delete();
                event(new PasswordReset($user));
            },
        );
    }
}
