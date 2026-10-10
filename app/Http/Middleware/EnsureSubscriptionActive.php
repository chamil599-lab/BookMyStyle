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

    private function ownerHasUsableSubscription($user): bool
    {
        return $user->hasActiveSubscription();
    }
}
