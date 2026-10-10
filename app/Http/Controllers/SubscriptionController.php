<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use App\Models\UserSubscription;
use App\RolesEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Filament\Facades\Filament;

class SubscriptionController extends Controller
{
    public function packages()
    {
        // Only for salon owners
        $user = Filament::auth()->user();
        if (!$user?->hasRole(RolesEnum::OWNER->value)) {
            abort(403);
        }

        $packages = SubscriptionPackage::active()
            ->ordered()
            ->get();

        $currentSubscription = Auth::user()->activeSubscription;

        return view('subscription.packages', compact('packages', 'currentSubscription'));
    }

    public function checkout(SubscriptionPackage $package)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user?->hasRole(RolesEnum::OWNER->value)) {
            abort(403);
        }

        // Block if pending (must wait for approval first)
        $pendingSub = $user->subscriptions()->where('status', 'pending')->first();
        if ($pendingSub) {
            return redirect()->route('subscription.required')
                ->with('error', 'You have a pending subscription. Please wait for admin approval before changing plans.');
        }

        // Check if this is a plan change (owner has active subscription)
        $activeSubscription = $user->activeSubscription;
        $isChangingPlan = $activeSubscription && $activeSubscription->package_id !== $package->id;

        return view('subscription.checkout', compact('package', 'isChangingPlan'));
    }

    public function subscribe(Request $request, SubscriptionPackage $package)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user?->hasRole(RolesEnum::OWNER->value)) {
            abort(403);
        }

        // Block if pending
        $pendingSub = $user->subscriptions()->where('status', 'pending')->first();
        if ($pendingSub) {
            return redirect()->route('subscription.required')
                ->with('error', 'You have a pending subscription. Please wait for admin approval.');
        }

        $request->validate([
            'payment_method' => 'required|in:bank_transfer,one_pay',
            'transaction_id' => 'required_if:payment_method,bank_transfer|nullable|string|max:255',
        ]);

        $featuresSnapshot = [
            'name' => $package->name,
            'max_salons' => $package->max_salons,
            'max_staff' => $package->max_staff,
            'max_services' => $package->max_services,
            'max_bookings_per_month' => $package->max_bookings_per_month,
            'analytics' => $package->analytics,
            'priority_support' => $package->priority_support,
        ];

        // Check if owner has an active subscription (plan change)
        $activeSubscription = $user->activeSubscription;

        if ($activeSubscription) {
            // Update existing subscription with new package
            $activeSubscription->update([
                'package_id' => $package->id,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'status' => 'pending',
                'amount_paid' => 0,
                'starts_at' => null,
                'ends_at' => null,
                'features_snapshot' => $featuresSnapshot,
            ]);
        } else {
            // Create new pending subscription
            UserSubscription::create([
                'user_id' => $user->id,
                'package_id' => $package->id,
                'amount_paid' => 0,
                'payment_method' => $request->payment_method,
                'transaction_id' => $request->transaction_id,
                'status' => 'pending',
                'starts_at' => null,
                'ends_at' => null,
                'features_snapshot' => $featuresSnapshot,
            ]);
        }

        return redirect('/admin/user-subscriptions')
            ->with('success', $activeSubscription
                ? 'Your plan change request has been submitted. Please wait for admin approval.'
                : 'Your subscription request has been submitted. Please wait for admin approval.');
    }
}