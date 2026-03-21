<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class AuthController extends Controller
{
    /**
     * Register a new user.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'friend_code' => User::generateFriendCode(),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Inscription réussie.', 201);
        } catch (Throwable $exception) {
            return $this->errorResponse('Erreur lors de l\'inscription.', ['exception' => $exception->getMessage()], 500);
        }
    }

    /**
     * Authenticate a user and return a new token.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($validated)) {
            return $this->errorResponse('Identifiants incorrects', null, 401);
        }

        try {
            /** @var User $user */
            $user = Auth::user();
            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ], 'Connexion réussie.');
        } catch (Throwable $exception) {
            return $this->errorResponse('Erreur lors de la connexion.', ['exception' => $exception->getMessage()], 500);
        }
    }

    /**
     * Revoke current user token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Déconnexion réussie.');
    }

    /**
     * Return authenticated user profile with counts.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadCount('gameSessions');

        return $this->successResponse([
            'user' => $user,
            'friends_count' => $user->friends->count(),
        ], 'Profil utilisateur récupéré.');
    }

    /**
     * Authenticate or register a user with Google OAuth token.
     */
    public function googleAuth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        try {
            $googleUser = Socialite::driver('google')->userFromToken($validated['token']);

            /** @var User|null $user */
            $user = User::query()
                ->where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            $created = false;

            if (! $user) {
                $user = User::create([
                    'google_id' => $googleUser->id,
                    'name' => $googleUser->name ?? $googleUser->nickname ?? 'Utilisateur Google',
                    'email' => $googleUser->email,
                    'password' => null,
                    'friend_code' => User::generateFriendCode(),
                ]);
                $created = true;
            } elseif (! $user->google_id) {
                $user->google_id = $googleUser->id;
                $user->save();
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
                'created' => $created,
            ], 'Authentification Google réussie.');
        } catch (Throwable $exception) {
            return $this->errorResponse('Échec de l\'authentification Google.', ['exception' => $exception->getMessage()], 422);
        }
    }

    /**
     * Build standardized success JSON response.
     */
    private function successResponse(mixed $data, string $message, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $status);
    }

    /**
     * Build standardized error JSON response.
     */
    private function errorResponse(string $message, mixed $errors = null, int $status = 422): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status);
    }
}
