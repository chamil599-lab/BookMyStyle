<?php

namespace App\Services;

use App\Mail\UserApproved;
use App\Mail\UserRejected;
use App\Models\SubscriptionPackage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserModeration
{
    public function approve(User $actor, User $target, ?string $role = null): User
    {
        return DB::transaction(function () use ($actor, $target, $role) {
            $record = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($actor->fresh())->authorize('update', $record);
            if ($record->is_approved) {
                throw ValidationException::withMessages(['role' => 'This user is already approved.']);
            }
            $role ??= $record->hasRole('owner') ? 'owner' : 'customer';
            Validator::make(['role' => $role], ['role' => 'required|in:owner,customer'])->validate();
            $record->syncRoles([$role]);
            $record->update(['is_approved' => true, 'approved_at' => now(), 'approved_by' => $actor->id]);
            if ($role === 'owner' && ($package = SubscriptionPackage::active()->where('slug', 'pro-salon')->first())) {
                app(SubscriptionService::class)->grantTrial($record, $package, $actor);
            }
            DB::afterCommit(fn () => Mail::to($record->email)->send(new UserApproved($record)));

            return $record;
        }, 3);
    }

    public function reject(User $actor, User $target): void
    {
        DB::transaction(function () use ($actor, $target) {
            $record = User::whereKey($target->id)->lockForUpdate()->firstOrFail();
            // Validate established-account restrictions before the deletion service
            // performs its stricter final delete authorization.
            Gate::forUser($actor->fresh())->authorize('update', $record);
            if ($record->is_approved) {
                throw ValidationException::withMessages(['user' => 'An approved user cannot be rejected.']);
            }
            // Never turn a stale rejection into deletion of an established account.
            if ($record->salons()->exists() || $record->subscriptions()->exists() || $record->bookings()->exists()) {
                throw ValidationException::withMessages(['user' => 'This account has records. Review it instead of rejecting it.']);
            }
            $email = $record->email;
            app(AccountDeletion::class)->managedDelete($actor, $record);
            DB::afterCommit(fn () => Mail::to($email)->send(new UserRejected($record)));
        }, 3);
    }
}
