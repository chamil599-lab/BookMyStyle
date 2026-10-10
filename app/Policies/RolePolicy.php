<?php

namespace App\Policies;

use App\Models\User;
use App\RolesEnum;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(RolesEnum::ADMIN->value);
    }

    public function view(User $user, Role $role): bool
    {
        return $user->hasRole(RolesEnum::ADMIN->value);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RolesEnum::ADMIN->value);
    }

    public function update(User $user, Role $role): bool
    {
        return $user->hasRole(RolesEnum::ADMIN->value);
    }

    public function delete(User $user, Role $role): bool
    {
        return $user->hasRole(RolesEnum::ADMIN->value);
    }
}
