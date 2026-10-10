<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use App\RolesEnum;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function packages()
    {
        // Only for salon owners
        $user = Filament::auth()->user();
        if (! $user?->hasRole(RolesEnum::OWNER->value)) {
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
        if (! $user?->hasRole(RolesEnum::OWNER->value)) {
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
        if (! $user?->hasRole(RolesEnum::OWNER->value)) {
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

        $activeSubscription = $user->activeSubscription()->first();
        app(\App\Services\SubscriptionService::class)->request($user, $package, $request->only(['payment_method', 'transaction_id']), $user);

        return redirect(\App\Filament\Admin\Resources\UserSubscriptions\UserSubscriptionResource::getUrl('index'))
            ->with('success', $activeSubscription
                ? 'Your plan change request has been submitted. Please wait for admin approval.'
                : 'Your subscription request has been submitted. Please wait for admin approval.');
    }
}
