<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\GoogleAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request, AuthService $service): JsonResponse
    {
        $result = $service->register($request->validated());

        return $this->success('Inscription réussie.', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ], 201);
    }

    public function login(LoginRequest $request, AuthService $service): JsonResponse
    {
        $result = $service->login(
            $request->validated('email'),
            $request->validated('password'),
        );

        return $this->success('Connexion réussie.', [
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'token_type' => 'Bearer',
        ]);
    }

    public function google(Request $request, GoogleAuthService $service): JsonResponse
    {
        $validated = $request->validate([
            'id_token' => ['required', 'string', 'max:8192'],
        ]);

        try {
            $result = $service->authenticate($validated['id_token']);

            return $this->success('Connexion Google réussie.', [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'created' => $result['created'],
            ]);
        } catch (InvalidArgumentException $exception) {
            return $this->error('Jeton Google invalide ou compte inactif.', [], 401);
        } catch (Throwable $exception) {
            Log::warning('Échec de vérification Google.', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return $this->error('La connexion Google est temporairement indisponible.', [], 503);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success('Déconnexion réussie.');
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()->tokens()->delete();

        return $this->success('Déconnexion effectuée sur tous les appareils.');
    }
}
