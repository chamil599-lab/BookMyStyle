<?php

namespace App\Http\Middleware;

use App\Models\Salon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();
        abort_unless($user, 401);

        // Skip for admins
        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return $next($request);
        }

        // Check if user has active subscription
        if (! $user->hasActiveSubscription()) {
            return redirect()->route('subscription.required')
                ->with('error', 'You need an active subscription to access this feature.');
        }

        // Check subscription limits
        $subscription = $user->activeSubscription()->with('package')->first();
        $package = $subscription?->package;
        abort_unless($package, 403);

        // Check salon limit
        if ($request->is('salons/create') || $request->routeIs('salons.store')) {
            $salonCount = $user->salons()->count();
            if ($salonCount >= $package->max_salons) {
                return redirect()->back()
                    ->with('error', "You've reached your salon limit ({$package->max_salons}). Upgrade your plan to add more.");
            }
        }

        // Check staff limit for specific salon
        if ($request->route('salon') && ($request->is('staff/create') || $request->routeIs('staff.store'))) {
            $salon = $request->route('salon');
            if (! $salon instanceof Salon) {
                $salon = Salon::findOrFail($salon);
            }
            abort_unless((int) $salon->created_by === (int) $user->id, 403);
            $staffCount = $salon->staff()->count();
            if ($staffCount >= $package->max_staff) {
                return redirect()->back()
                    ->with('error', "Staff limit reached ({$package->max_staff}). Upgrade your plan.");
            }
        }

        if (($request->is('bookings/create') || $request->routeIs('bookings.store')) && ! $user->canCreateMoreBookings()) {
            return redirect()->back()->with('error', 'Monthly booking limit reached. Upgrade your plan.');
        }

        return $next($request);
    }
}
