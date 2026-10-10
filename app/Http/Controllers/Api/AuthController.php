<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private function userPayload(User $user): array
    {
        return [
            'id'          => $user->id,
            'name'        => $user->name,
            'email'       => $user->email,
            'role'        => $user->getRoleNames()->first(),
            'avatar'      => $user->avatar,
            'is_approved' => (bool) $user->is_approved,
        ];
    }

    /**
     * Register a new customer
     */
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'nullable|in:customer,owner',
            'phone'    => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
        ]);

        $role = $validated['role'] ?? 'customer';

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'phone'       => $validated['phone'] ?? null,
            'location'    => $validated['location'] ?? null,
            'is_approved' => $role === 'owner' ? false : true,
        ]);

        $user->assignRole($role);

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => $this->userPayload($user),
            'token' => $token
        ], 201);
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

        $user = User::where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->hasRole('owner') && !$user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is pending admin approval.',
            ], 403);
        }

        // Create token
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $this->userPayload($user),
            'token' => $token
        ]);
    }

    /**
     * Sign in / sign up via Google ID token.
     */
    public function googleAuth(Request $request)
    {
        $validated = $request->validate([
            'credential' => 'required|string',
            'role'       => 'nullable|in:customer,owner',
        ]);

        $clientId = config('services.google.client_id');
        if (!$clientId) {
            return response()->json([
                'success' => false,
                'message' => 'Google sign-in is not configured on the server.',
            ], 500);
        }

        try {
            $client  = new \Google\Client(['client_id' => $clientId]);
            $payload = $client->verifyIdToken($validated['credential']);
        } catch (\Throwable $e) {
            $payload = null;
        }

        if (!$payload || empty($payload['sub']) || empty($payload['email'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired Google token.',
            ], 401);
        }

        $requestedRole = $validated['role'] ?? 'customer';

        $user = User::where('google_id', $payload['sub'])
            ->orWhere('email', $payload['email'])
            ->first();

        $isNewUser = false;

        if (!$user) {
            $user = User::create([
                'name'              => $payload['name'] ?? $payload['email'],
                'email'             => $payload['email'],
                'email_verified_at' => now(),
                'google_id'         => $payload['sub'],
                'avatar'            => $payload['picture'] ?? null,
                'password'          => null,
                'is_approved'       => $requestedRole === 'owner' ? false : true,
            ]);
            $user->assignRole($requestedRole);
            $isNewUser = true;
        } else {
            $updates = [];
            if (!$user->google_id) {
                $updates['google_id'] = $payload['sub'];
            }
            if (empty($user->avatar) && !empty($payload['picture'])) {
                $updates['avatar'] = $payload['picture'];
            }
            if (!$user->email_verified_at) {
                $updates['email_verified_at'] = now();
            }
            if (!empty($updates)) {
                $user->update($updates);
            }

            if ($user->getRoleNames()->isEmpty()) {
                $user->assignRole($requestedRole);
            }
        }

        $role = $user->getRoleNames()->first();

        if ($role === 'owner' && !$user->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Your salon owner account is pending admin approval.',
            ], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => $isNewUser ? 'Account created with Google' : 'Login successful',
            'user'    => $this->userPayload($user),
            'token'   => $token,
        ], $isNewUser ? 201 : 200);
    }

    /**
     * Logout customer (revoke current token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
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
        $validated = $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        // Check if current password is correct
        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect'
            ], 400);
        }

        // Update password
        $user->update([
            'password' => Hash::make($validated['password'])
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
    }

    /**
     * Send password reset link
     */
    public function forgotPassword(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        $status = Password::sendResetLink($validated);

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset link sent to your email'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Unable to send reset link'
        ], 400);
    }

    /**
     * Reset password using token
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => __($status)
        ], 400);
    }
}
