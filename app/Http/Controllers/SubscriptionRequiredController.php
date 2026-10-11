<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPackage;
use App\Support\ManagementAccess;
use Illuminate\Http\Request;

class SubscriptionRequiredController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()?->fresh();
        abort_unless(ManagementAccess::owner($user), 403);

        return view('subscription.required', [
            'packages' => SubscriptionPackage::active()->ordered()->get()->filter(fn ($package) => $package->isAvailable()),
            'pendingSub' => $user->subscriptions()->pending()->with('package')->first(),
            'activeSub' => $user->activeSubscription,
        ]);
    }
}
