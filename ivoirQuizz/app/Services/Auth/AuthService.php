<?php

namespace App\Services\Auth;

use App\Enums\UserStatus;
use App\Exceptions\Auth\AccountInactiveException;
use App\Models\User;
use App\Models\UserLife;
use App\Models\UserStreak;
use App\Services\Game\ProgressionService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private ProgressionService $progression) {}

    /**
     * @param  array{name: string, username: string, email: string, phone?: string|null, password: string}  $data
     * @return array{user: User, token: string}
     */
    public function register(array $data): array
    {
        return DB::transaction(function () use ($data): array {
            $user = User::query()->create([
                'name' => $data['name'],
                'username' => mb_strtolower($data['username']),
                'email' => mb_strtolower($data['email']),
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'friend_code' => User::generateFriendCode(),
                'status' => UserStatus::Active,
                'last_activity_date' => today(),
            ]);

            $this->progression->initializeForUser($user);
            UserLife::query()->firstOrCreate(['user_id' => $user->id], ['lives' => 5, 'max_lives' => 5]);
            UserStreak::query()->firstOrCreate(['user_id' => $user->id], ['current_streak' => 0, 'longest_streak' => 0, 'streak_freezes' => 0]);

            return [
                'user' => $user->refresh(),
                'token' => $user->createToken('mobile')->plainTextToken,
            ];
        });
    }

    /** @return array{user: User, token: string} */
    public function login(string $email, string $password): array
    {
        $user = User::query()->where('email', mb_strtolower($email))->first();

        if (! $user || ! Hash::check($password, (string) $user->password)) {
            throw new AuthenticationException('Identifiants invalides.');
        }

        if ($user->status !== UserStatus::Active) {
            throw new AccountInactiveException('Ce compte n’est pas actif.');
        }

        if (Hash::needsRehash((string) $user->password)) {
            $user->forceFill(['password' => Hash::make($password)]);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_activity_date' => today(),
        ])->save();

        return [
            'user' => $user->refresh(),
            'token' => $user->createToken('mobile')->plainTextToken,
        ];
    }
}
