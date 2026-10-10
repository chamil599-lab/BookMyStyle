<?php

namespace App\Services;

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Support\ManagementAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PackageManagement
{
    public function archive(User $actor, SubscriptionPackage $package): bool
    {
        abort_unless(ManagementAccess::admin($actor->fresh()), 403);

        return DB::transaction(function () use ($package) {
            $record = SubscriptionPackage::whereKey($package->id)->lockForUpdate()->firstOrFail();
            if ($record->subscriptions()->where(fn ($q) => $q->where('status', 'pending')
                ->orWhere(fn ($q) => $q->where('status', 'active')->where('ends_at', '>', now())))->exists()) {
                throw ValidationException::withMessages(['package' => 'This package has pending, active or scheduled subscriptions. Disable new purchases instead.']);
            }

            return (bool) $record->delete();
        }, 3);
    }
}
