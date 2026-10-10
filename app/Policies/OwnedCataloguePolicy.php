<?php

namespace App\Policies;

use App\Models\User;
use App\Support\ManagementAccess;
use Illuminate\Database\Eloquent\Model;

class OwnedCataloguePolicy extends AdminOnlyPolicy
{
    public function viewAny(User $user): bool
    {
        return ManagementAccess::admin($user) || ManagementAccess::owner($user);
    }

    public function view(User $user, Model $record): bool
    {
        return ManagementAccess::admin($user) || (ManagementAccess::owner($user) && (int) $record->created_by === (int) $user->id);
    }

    public function create(User $user): bool
    {
        return ManagementAccess::admin($user) || ManagementAccess::owner($user, true);
    }

    public function update(User $user, Model $record): bool
    {
        return ManagementAccess::admin($user) || (ManagementAccess::owner($user, true) && (int) $record->created_by === (int) $user->id);
    }

    public function delete(User $user, Model $record): bool
    {
        return $this->update($user, $record);
    }

    public function deleteAny(User $user): bool
    {
        return ManagementAccess::admin($user);
    }
}
