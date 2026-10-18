<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\Role;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Category $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::SLUG_ADMINISTRATOR);
    }

    public function update(User $user, Category $category): bool
    {
        return $user->hasRole(Role::SLUG_ADMINISTRATOR);
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->hasRole(Role::SLUG_ADMINISTRATOR);
    }
}
