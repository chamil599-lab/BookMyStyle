<?php

namespace App\Support;

use App\Models\User;

class ManagementAccess
{
    public static function admin(?User $user): bool
    {
        return $user?->hasVerifiedEmail() && $user->hasRole('admin');
    }

    public static function owner(?User $user, bool $active = false): bool
    {
        return $user?->hasVerifiedEmail() && $user->hasRole('owner') && $user->is_approved
            && (! $active || $user->hasActiveSubscription());
    }
}
