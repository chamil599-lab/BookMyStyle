<?php

namespace App\Policies;

use App\Models\User;
use App\Support\ManagementAccess;
use Illuminate\Database\Eloquent\Model;

class AdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return ManagementAccess::admin($user);
    }

    public function view(User $user, Model $record): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, Model $record): bool
    {
        return $this->viewAny($user);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->viewAny($user);
    }

    public function deleteAny(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function restore(User $user, Model $record): bool
    {
        return ManagementAccess::admin($user);
    }

    public function restoreAny(User $user): bool
    {
        return ManagementAccess::admin($user);
    }

    public function forceDelete(User $user, Model $record): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }
}
