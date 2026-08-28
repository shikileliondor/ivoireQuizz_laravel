<?php

namespace App\Policies;

use App\Enums\UserStatus;
use App\Models\User;

class UserPolicy
{
    public function update(User $authenticatedUser, User $targetUser): bool
    {
        return $authenticatedUser->is($targetUser) && $authenticatedUser->status === UserStatus::Active;
    }

    public function delete(User $authenticatedUser, User $targetUser): bool
    {
        return $this->update($authenticatedUser, $targetUser);
    }
}
