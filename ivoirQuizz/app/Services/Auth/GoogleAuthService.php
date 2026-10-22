<?php

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\UserLife;
use App\Models\UserStreak;
use App\Services\Game\ProgressionService;
use Google\Auth\AccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

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

        $claims = (new AccessToken())->verify($idToken, [
            'audience' => $clientId,
            'issuer' => 'https://accounts.google.com',
        ]);

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
}
