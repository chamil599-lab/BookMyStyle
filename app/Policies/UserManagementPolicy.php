<?php

namespace App\Policies;

use App\Models\User;
use App\Support\ManagementAccess;
use Illuminate\Database\Eloquent\Model;

class UserManagementPolicy extends AdminOnlyPolicy
{
    public function update(User $user, Model $record): bool
    {
        return ManagementAccess::admin($user) && ! $record->hasRole('admin');
    }

    public function delete(User $user, Model $record): bool
    {
        return ManagementAccess::admin($user) && ! $record->hasRole('admin') && $user->id !== $record->id;
    }
}
