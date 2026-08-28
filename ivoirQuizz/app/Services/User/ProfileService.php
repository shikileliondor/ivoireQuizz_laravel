<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    /** @param array<string, mixed> $data */
    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            if (isset($data['username'])) {
                $data['username'] = mb_strtolower($data['username']);
            }

            $user->fill($data);
            $user->forceFill(['last_activity_date' => today()])->save();

            return $user->refresh();
        });
    }

    public function updatePassword(User $user, string $password): void
    {
        DB::transaction(function () use ($user, $password): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $lockedUser->forceFill(['password' => Hash::make($password)])->save();
            $lockedUser->tokens()->delete();
        });
    }
}
