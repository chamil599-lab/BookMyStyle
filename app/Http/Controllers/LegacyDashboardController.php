<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LegacyDashboardController extends Controller
{
    public function __invoke(Request $request, ?string $path = null)
    {
        $owner = $request->is('owner', 'owner/*');
        $parts = explode('/', $path ?? '');
        $section = $parts[0];
        if ($section === '' || $section === 'dashboard') {
            return redirect()->route($owner ? 'filament.admin.pages.owner-dashboard' : 'filament.admin.pages.dashboard');
        }
        if ($section === 'profile') {
            return redirect()->route('profile');
        }
        $resources = ['salons' => 'salons', 'bookings' => 'bookings', 'services' => 'services',
            'users' => 'users', 'subscriptions' => 'user-subscriptions', 'subscription' => 'user-subscriptions',
            'packages' => 'subscription-packages', 'categories' => 'categories', 'departments' => 'departments'];
        abort_unless(isset($resources[$section]), 404);
        $page = isset($parts[1]) ? ($parts[1] === 'create' ? 'create' : (isset($parts[2]) ? 'edit' : 'view')) : 'index';
        $parameters = isset($parts[1]) && $parts[1] !== 'create' ? ['record' => $parts[1]] : [];
        $route = 'filament.admin.resources.'.$resources[$section].'.'.$page;
        abort_unless(\Illuminate\Support\Facades\Route::has($route), 404);

        return redirect()->route($route, $parameters);
    }
}
