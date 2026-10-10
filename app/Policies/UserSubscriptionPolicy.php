<?php

namespace App\Policies;

use App\Models\User;
use App\Support\ManagementAccess;
use Illuminate\Database\Eloquent\Model;

class UserSubscriptionPolicy extends AdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return ManagementAccess::admin($user) || ManagementAccess::owner($user);
    }

    public function view(User $user, Model $record): bool
    {
        return ManagementAccess::admin($user) || (ManagementAccess::owner($user) && (int) $record->user_id === (int) $user->id);
    }

    public function create(User $user): bool
    {
        return ManagementAccess::admin($user);
    }

    public function update(User $user, Model $record): bool
    {
        return ManagementAccess::admin($user) && $record->isPending();
    }

    public function delete(User $user, Model $record): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
