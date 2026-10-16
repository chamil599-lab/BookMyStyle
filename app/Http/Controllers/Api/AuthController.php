<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Livewire\Actions\Logout;
use App\Models\User;
use App\Services\AccountService;
use App\Services\ApiTokenService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->getRoleNames()->first(),
            'avatar' => $user->avatar,
            'is_approved' => (bool) $user->is_approved,
        ];
    }

    /**
     * Register a new customer
     */
    public function register(Request $request)
    {
        $user = app(AccountService::class)->register($request->all());
        $token = app(ApiTokenService::class)->issue($user)->plainTextToken;

        return response()->json(['success' => true, 'message' => 'Registration successful', 'user' => $this->userPayload($user), 'token' => $token], 201);
    }

    /**
     * Login customer
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = app(AccountService::class)->credentials($validated['email'], $validated['password']);

        if ($user->hasRole('owner') && ! $user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending admin approval.',
            ], 403);
        }

        // Create token
        $token = app(ApiTokenService::class)->issue($user)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    /**
     * Sign in / sign up via Google ID token.
     */
    public function googleAuth(Request $request)
    {
        $validated = $request->validate(['credential' => 'required|string|max:10000', 'role' => 'nullable|in:customer,owner']);
        $user = app(AccountService::class)->google($validated['credential'], $validated['role'] ?? 'customer');
        if ($user->hasRole('owner') && ! $user->is_approved) {
            return response()->json(['success' => false, 'message' => 'Your salon owner account is pending admin approval.'], 403);
        }
        $token = app(ApiTokenService::class)->issue($user)->plainTextToken;

        return response()->json(['success' => true, 'message' => 'Login successful', 'user' => $this->userPayload($user), 'token' => $token], $user->wasRecentlyCreated ? 201 : 200);
    }

    /**
     * Logout customer (revoke current token)
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        } elseif ($request->hasSession()) {
            app(Logout::class)();
        } else {
            Auth::guard('web')->logout();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user
     */
    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                ...$this->userPayload($user),
                'created_at' => $user->created_at->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(Request $request)
    {
        app(\App\Services\AuthActionLimiter::class)->check('password-change', $request->user()->email, 5, 'current_password');
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Check if current password is correct
        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate(['email' => 'required|email|max:255']);
        Password::sendResetLink($validated);

        return response()->json(['success' => true, 'message' => 'If an account exists for that email, a password reset link has been sent.']);
    }

    /**
     * Reset password using token
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __($status),
        ], 400);
    }
}
