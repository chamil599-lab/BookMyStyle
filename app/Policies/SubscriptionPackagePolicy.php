<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPackagePolicy extends AdminOnlyPolicy
{
    public function delete(User $user, Model $record): bool
    {
        return parent::delete($user, $record) && ! $record->subscriptions()
            ->where(fn ($q) => $q->where('status', 'pending')->orWhere(fn ($q) => $q->where('status', 'active')->where('ends_at', '>', now())))
            ->exists();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
