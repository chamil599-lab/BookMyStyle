<?php

namespace App\Http\Controllers;

use App\Filament\Admin\Pages\SubscriptionPlans;
use App\Support\ManagementAccess;
use Illuminate\Http\Request;

class SubscriptionRequiredController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user()?->fresh();
        abort_unless(ManagementAccess::owner($user), 403);

        return redirect(SubscriptionPlans::getUrl(panel: 'admin'));
    }
}
