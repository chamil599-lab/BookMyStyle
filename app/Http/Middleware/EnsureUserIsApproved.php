<?php

namespace App\Http\Middleware;

use App\RolesEnum;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && $user->hasRole(RolesEnum::OWNER->value) && ! $user->is_approved) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is pending admin approval.',
                ], 403);
            }

            if (! $request->routeIs('filament.admin.pages.pending-approval')
                && ! $request->routeIs('filament.admin.auth.email-verification.*')
                && ! $request->routeIs('filament.admin.auth.logout')) {
                return redirect()->route('filament.admin.pages.pending-approval');
            }
        }

        return $next($request);
    }
}
