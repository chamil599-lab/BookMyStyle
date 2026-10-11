<?php

namespace App\Http\Controllers;

use App\Support\ManagementAccess;
use Illuminate\Http\Request;

class RetiredManagementController extends Controller
{
    public function __invoke(Request $request)
    {
        $actor = $request->user()?->fresh();
        $admin = $request->is('api/admin/*');
        abort_unless($admin ? ManagementAccess::admin($actor) : ManagementAccess::owner($actor), 403);
        $destination = $request->route('native_route');
        $nativeUrl = route($destination);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Robots-Tag' => 'noindex, nofollow'];

        // A redirect could replay a write or falsely report a successful mutation.
        return $request->is('api/*') || $request->expectsJson()
            ? response()->json(['code' => 'endpoint_retired', 'message' => 'Manage this account through the native dashboard.',
                'next_url' => $nativeUrl], 410, $headers)
            : response()->view('errors.retired-management', ['nativeUrl' => $nativeUrl], 410, $headers);
    }
}
