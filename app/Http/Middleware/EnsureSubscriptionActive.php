<?php

namespace App\Http\Middleware;

use App\RolesEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->hasRole(RolesEnum::OWNER->value) && ! $this->ownerHasUsableSubscription($user)) {
            // Whitelist subscription endpoints so the user can still view packages, subscribe, and check history
            // from the locked subscription page.
            if ($request->is('api/owner/subscription/*') || $request->is('api/owner/subscription')) {
                return $next($request);
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'code' => 'subscription_required',
                    'message' => 'Your subscription is not active. Reactivate or switch to a plan to continue.',
                ], 403);
            }

            return redirect()->route('subscription.required');
        }

        return $next($request);
    }

    /**
     * Stricter than User::hasActiveSubscription(): also rejects when the subscription
     * ends today (days_remaining <= 0). Filament's relation only checks ends_at > now()
     * which keeps a sub "active" until the literal final timestamp, but operationally
     * a 0-days-remaining owner should be treated as expired.
     */
    private function ownerHasUsableSubscription($user): bool
    {
        $sub = $user->activeSubscription;
        if (! $sub) return false;
        return $sub->daysRemaining() > 0;
    }
}
