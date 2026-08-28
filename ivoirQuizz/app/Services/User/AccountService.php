<?php

namespace App\Services\User;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountService
{
    public function delete(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $suffix = $lockedUser->id.'_'.Str::lower(Str::random(12));

            $lockedUser->tokens()->delete();
            $lockedUser->forceFill([
                'name' => 'Compte supprimé',
                'username' => 'deleted_'.$suffix,
                'email' => 'deleted+'.$suffix.'@example.invalid',
                'phone' => null,
                'password' => Hash::make(Str::random(64)),
                'google_id' => null,
                'avatar' => null,
                'city' => null,
                'bio' => null,
                'status' => UserStatus::Disabled,
                'remember_token' => null,
            ])->save();
            $lockedUser->delete();
        });
    }
}
