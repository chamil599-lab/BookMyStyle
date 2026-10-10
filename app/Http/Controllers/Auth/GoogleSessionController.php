<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoogleSessionController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = $request->validate(['credential' => 'required|string|max:10000', 'role' => 'sometimes|in:customer,owner']);
        $user = app(AccountService::class)->google($data['credential'], $data['role'] ?? 'customer');
        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
