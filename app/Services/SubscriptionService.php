<?php

namespace App\Services;

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public function grantTrial(User $owner, SubscriptionPackage $package, User $actor): ?UserSubscription
    {
        abort_unless($actor->hasRole('admin') && $owner->hasRole('owner'), 403);

        return DB::transaction(function () use ($owner, $package) {
            User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
            $package = SubscriptionPackage::whereKey($package->id)->lockForUpdate()->firstOrFail();
            if ($owner->subscriptions()->exists() || ! $package->isAvailable()) {
                return null;
            }

            return UserSubscription::create(['user_id' => $owner->id, 'package_id' => $package->id,
                'status' => 'active', 'amount_paid' => 0, 'is_trial' => true, 'starts_at' => now(),
                'ends_at' => now()->addDays(90), 'features_snapshot' => $package->only(['name', 'price', 'max_salons',
                    'max_staff', 'max_services', 'max_bookings_per_month', 'analytics', 'priority_support', 'calendar', 'features']) + ['duration_days' => 90]]);
        }, 3);
    }

    public function request(User $owner, SubscriptionPackage $package, array $payment, User $actor): UserSubscription
    {
        abort_unless($owner->hasRole('owner'), 403);
        abort_unless($actor->hasRole('admin') || ($actor->id === $owner->id && $owner->hasRole('owner')), 403);
        $payment = Validator::make($payment, [
            'payment_method' => 'required|in:bank_transfer,one_pay',
            'transaction_id' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
        ])->validate();

        return DB::transaction(function () use ($owner, $package, $payment) {
            User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
            $package = SubscriptionPackage::whereKey($package->id)->lockForUpdate()->firstOrFail();
            if (! $package->isAvailable() || $package->duration_days < 1) {
                throw ValidationException::withMessages(['package_id' => 'This package is unavailable.']);
            }
            if ($owner->subscriptions()->pending()->exists()) {
                throw ValidationException::withMessages(['package_id' => 'You already have a pending subscription request.']);
            }
            $current = $owner->subscriptions()->where('status', 'active')->where('ends_at', '>', now())->orderByDesc('ends_at')->first();

            return UserSubscription::create([
                'user_id' => $owner->id, 'package_id' => $package->id,
                'amount_paid' => 0, 'status' => 'pending',
                'payment_method' => $payment['payment_method'], 'transaction_id' => $payment['transaction_id'] ?? null,
                'renewed_from' => $current && $current->package_id === $package->id ? $current->id : null,
                'features_snapshot' => $package->only(['name', 'price', 'duration_days', 'max_salons', 'max_staff', 'max_services', 'max_bookings_per_month', 'analytics', 'priority_support', 'calendar', 'features']),
            ]);
        }, 3);
    }

    public function approve(UserSubscription $subscription, User $actor, mixed $amount): UserSubscription
    {
        abort_unless($actor->hasRole('admin'), 403);
        $amount = Validator::make(['amount_paid' => $amount], ['amount_paid' => 'required|numeric|min:0'])->validate()['amount_paid'];

        return DB::transaction(function () use ($subscription, $amount) {
            User::whereKey($subscription->user_id)->lockForUpdate()->firstOrFail();
            $record = UserSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            if (! $record->isPending()) {
                throw ValidationException::withMessages(['status' => 'Only pending requests can be approved.']);
            }
            $package = $record->package;
            $duration = (int) ($record->features_snapshot['duration_days'] ?? $package?->duration_days);
            if ($duration < 1) {
                throw ValidationException::withMessages(['package_id' => 'A valid package duration is required.']);
            }
            $active = UserSubscription::where('user_id', $record->user_id)->where('status', 'active')->where('ends_at', '>', now())->lockForUpdate()->get();
            $samePlan = $active->isNotEmpty() && $active->every(fn ($sub) => $sub->package_id === $record->package_id);
            $start = $samePlan ? $active->sortByDesc('ends_at')->first()->ends_at->copy() : now();
            if (! $samePlan) {
                foreach ($active as $previous) {
                    $previous->update(['status' => 'canceled', 'cancelled_at' => now()]);
                }
            }
            $record->update(['status' => 'active', 'amount_paid' => $amount, 'starts_at' => $start,
                'ends_at' => $start->copy()->addDays($duration), 'cancelled_at' => null]);

            return $record;
        }, 3);
    }

    public function cancel(UserSubscription $subscription, User $actor, bool $pendingOnly = false): UserSubscription
    {
        abort_unless($actor->hasRole('admin'), 403);

        return DB::transaction(function () use ($subscription, $pendingOnly) {
            User::whereKey($subscription->user_id)->lockForUpdate()->firstOrFail();
            $record = UserSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            if (! in_array($record->status, $pendingOnly ? ['pending'] : ['pending', 'active'], true)) {
                throw ValidationException::withMessages(['status' => 'This subscription cannot be cancelled.']);
            }
            $record->update(['status' => 'canceled', 'cancelled_at' => now()]);

            return $record;
        }, 3);
    }

    public function updatePayment(UserSubscription $subscription, User $actor, array $payment): UserSubscription
    {
        abort_unless($actor->hasRole('admin') || ($actor->hasRole('owner') && (int) $actor->id === (int) $subscription->user_id), 403);
        $payment = Validator::make($payment, ['payment_method' => 'required|in:bank_transfer,one_pay',
            'transaction_id' => 'required_if:payment_method,bank_transfer|nullable|string|max:255'])->validate();
        $payment['transaction_id'] = $payment['payment_method'] === 'bank_transfer' ? $payment['transaction_id'] : null;

        return DB::transaction(function () use ($subscription, $payment) {
            User::whereKey($subscription->user_id)->lockForUpdate()->firstOrFail();
            $record = UserSubscription::whereKey($subscription->id)->lockForUpdate()->firstOrFail();
            if (! $record->isPending()) {
                throw ValidationException::withMessages(['payment_method' => 'Only pending payment details can be changed.']);
            }
            $record->update($payment);

            return $record;
        }, 3);
    }
}
