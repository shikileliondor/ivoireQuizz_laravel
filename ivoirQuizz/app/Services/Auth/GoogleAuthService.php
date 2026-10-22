<?php

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserLife;
use App\Models\UserStreak;
use App\Services\Game\ProgressionService;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class GoogleAuthService
{
    public function __construct(private ProgressionService $progression) {}

    /** @return array{user: User, token: string, created: bool} */
    public function authenticate(string $idToken): array
    {
        $clientId = (string) config('services.google.client_id');
        if ($clientId === '') {
            throw new RuntimeException('Google client ID is not configured.');
        }

        $claims = (array) $this->verifyIdToken($idToken, $clientId);

        if (! is_array($claims)
            || empty($claims['sub'])
            || empty($claims['email'])
            || ! filter_var($claims['email'], FILTER_VALIDATE_EMAIL)
            || ! in_array($claims['email_verified'] ?? false, [true, 'true', 1, '1'], true)) {
            throw new InvalidArgumentException('Le jeton Google ne contient pas une identité vérifiée.');
        }

        return DB::transaction(function () use ($claims): array {
            $user = User::query()->where('google_id', $claims['sub'])->first();

            if (! $user) {
                $user = User::query()->where('email', mb_strtolower($claims['email']))->first();
            }

            $created = false;
            if (! $user) {
                $user = User::query()->create([
                    'name' => mb_substr((string) ($claims['name'] ?? 'Utilisateur Google'), 0, 100),
                    'email' => mb_strtolower($claims['email']),
                    'google_id' => $claims['sub'],
                    'password' => Hash::make(Str::random(64)),
                    'friend_code' => User::generateFriendCode(),
                    'status' => UserStatus::Active,
                    'email_verified_at' => now(),
                    'last_activity_date' => today(),
                ]);

                $this->progression->initializeForUser($user);
                UserLife::query()->firstOrCreate(['user_id' => $user->id], ['lives' => 5, 'max_lives' => 5]);
                UserStreak::query()->firstOrCreate(['user_id' => $user->id], [
                    'current_streak' => 0,
                    'longest_streak' => 0,
                    'streak_freezes' => 0,
                ]);
                $created = true;
            } elseif ($user->google_id === null) {
                $user->forceFill(['google_id' => $claims['sub'], 'email_verified_at' => $user->email_verified_at ?? now()])->save();
            }

            if ($user->status !== UserStatus::Active) {
                throw new InvalidArgumentException('Ce compte n’est pas actif.');
            }

            $user->forceFill(['last_login_at' => now(), 'last_activity_date' => today()])->save();

            return [
                'user' => $user->refresh(),
                'token' => $user->createToken('mobile')->plainTextToken,
                'created' => $created,
            ];
        });
    }

    private function verifyIdToken(string $idToken, string $clientId): object
    {
        if (count(explode('.', $idToken)) !== 3) {
            throw new InvalidArgumentException('Malformed Google ID token.');
        }

        $jwks = Cache::store('file')->remember('google:oidc:jwks', now()->addHour(), function (): array {
            $response = Http::timeout(5)->get('https://www.googleapis.com/oauth2/v3/certs');
            if (! $response->successful()) {
                throw new RuntimeException('Google signing certificates could not be fetched.');
            }

            return $response->json();
        });

        try {
            $claims = JWT::decode($idToken, JWK::parseKeySet($jwks));
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('Invalid Google ID token.', previous: $exception);
        }

        $audiences = is_array($claims->aud ?? null) ? $claims->aud : [$claims->aud ?? null];
        if (! in_array($clientId, $audiences, true)
            || ! in_array($claims->iss ?? null, ['accounts.google.com', 'https://accounts.google.com'], true)
            || (count($audiences) > 1 && ($claims->azp ?? null) !== $clientId)) {
            throw new InvalidArgumentException('Google ID token claims do not match this application.');
        }

        return $claims;
    }
}
