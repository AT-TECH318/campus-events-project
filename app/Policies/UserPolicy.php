<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::SLUG_ADMINISTRATOR);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasRole(Role::SLUG_ADMINISTRATOR);
    }

    public function updateRole(User $user, User $model): bool
    {
        return $user->hasRole(Role::SLUG_ADMINISTRATOR);
    }
}
