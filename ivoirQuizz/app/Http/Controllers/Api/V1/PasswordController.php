<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Profile\UpdatePasswordRequest;
use App\Services\Auth\PasswordResetService;
use App\Services\User\ProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class PasswordController extends Controller
{
    use ApiResponse;

    public function forgot(ForgotPasswordRequest $request, PasswordResetService $service): JsonResponse
    {
        $service->sendLink($request->validated('email'));

        return $this->success('Si cette adresse existe, un lien de réinitialisation sera envoyé.');
    }

    public function reset(ResetPasswordRequest $request, PasswordResetService $service): JsonResponse
    {
        $status = $service->reset($request->validated());

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error('Le lien de réinitialisation est invalide ou expiré.', [
                'email' => [__($status)],
            ], 422);
        }

        return $this->success('Mot de passe réinitialisé. Vous pouvez maintenant vous connecter.');
    }

    public function update(UpdatePasswordRequest $request, ProfileService $service): JsonResponse
    {
        $this->authorize('update', $request->user());
        $service->updatePassword($request->user(), $request->validated('password'));

        return $this->success('Mot de passe modifié. Veuillez vous reconnecter.');
    }
}
