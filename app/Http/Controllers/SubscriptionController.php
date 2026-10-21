<?php

namespace App\Http\Controllers;

use App\Filament\Admin\Pages\SubscriptionCheckout;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\SubscriptionService;
use App\Support\ManagementAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscriptionController extends Controller
{
    public function packages()
    {
        abort_unless(ManagementAccess::owner(Auth::user()?->fresh()), 403);

        return redirect()->route('subscription.required');
    }

    public function checkout(SubscriptionPackage $package)
    {
        /** @var User $user */
        $user = Auth::user();
        if (! ManagementAccess::owner($user?->fresh())) {
            abort(403);
        }

        abort_unless($package->isAvailable(), 404);

        // Block if pending (must wait for approval first)
        $pendingSub = $user->subscriptions()->where('status', 'pending')->first();
        if ($pendingSub) {
            return redirect()->route('subscription.required')
                ->with('error', 'You have a pending subscription. Please wait for admin approval before changing plans.');
        }

        return redirect(SubscriptionCheckout::getUrl(['package' => $package->id], panel: 'admin'));
    }

    public function subscribe(Request $request, SubscriptionPackage $package)
    {
        /** @var User $user */
        $user = Auth::user();
        if (! ManagementAccess::owner($user?->fresh())) {
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
        app(SubscriptionService::class)->request($user, $package, $request->only(['payment_method', 'transaction_id']), $user);

        return redirect()->route('filament.admin.resources.user-subscriptions.index')
            ->with('success', $activeSubscription
                ? 'Your plan change request has been submitted. Please wait for admin approval.'
                : 'Your subscription request has been submitted. Please wait for admin approval.');
    }
}
