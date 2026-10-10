<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class AccountService
{
    public function register(array $data, bool $dispatchEvent = true): User
    {
        if (is_string($data['email'] ?? null)) {
            $data['email'] = strtolower(trim($data['email']));
        }
        $data = Validator::make($data, ['name' => 'required|string|max:255', 'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'string', 'confirmed', Password::defaults()], 'role' => 'nullable|in:customer,owner',
            'phone' => 'nullable|string|max:20', 'location' => 'nullable|string|max:255'])->validate();
        $user = DB::transaction(function () use ($data) {
            $role = $data['role'] ?? 'customer';
            Role::findOrCreate($role, 'web');
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'],
                'phone' => $data['phone'] ?? null, 'location' => $data['location'] ?? null, 'is_approved' => $role === 'customer']);
            $user->assignRole($role);

            return $user;
        }, 3);
        if ($dispatchEvent) {
            event(new Registered($user));
        }

        return $user;
    }

    public function credentials(string $email, string $password): User
    {
        $user = User::where('email', strtolower(trim($email)))->first();
        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        return $user;
    }

    public function google(string $credential, string $role = 'customer'): User
    {
        abort_unless(in_array($role, ['customer', 'owner'], true), 422);
        $payload = app(GoogleIdentityVerifier::class)->verify($credential);
        if (empty($payload['sub']) || empty($payload['email']) || ! in_array($payload['email_verified'] ?? false, [true, 'true', 1], true)) {
            throw ValidationException::withMessages(['credential' => 'Invalid or expired Google credential.']);
        }
        $email = strtolower($payload['email']);

        return DB::transaction(function () use ($payload, $email, $role) {
            $byIdentity = User::where('google_id', $payload['sub'])->lockForUpdate()->first();
            $byEmail = User::where('email', $email)->lockForUpdate()->first();
            if (($byIdentity && $byEmail && $byIdentity->id !== $byEmail->id) || ($byEmail?->google_id && $byEmail->google_id !== $payload['sub'])) {
                throw ValidationException::withMessages(['credential' => 'This Google identity conflicts with an existing account. Sign in with your password.']);
            }
            $user = $byIdentity ?? $byEmail;
            if (! $user) {
                Role::findOrCreate($role, 'web');
                $user = User::create(['name' => $payload['name'] ?? $email, 'email' => $email, 'email_verified_at' => now(),
                    'google_id' => $payload['sub'], 'avatar' => $payload['picture'] ?? null, 'password' => null,
                    'is_approved' => $role === 'customer']);
                $user->assignRole($role);

                return $user;
            }
            // Google is authoritative for Gmail and Workspace email. A third-party
            // verified email claim alone cannot link an existing password account.
            if (! $byIdentity && ! str_ends_with($email, '@gmail.com') && empty($payload['hd'])) {
                throw ValidationException::withMessages(['credential' => 'Sign in with your password to use this existing account.']);
            }
            $updates = ['google_id' => $payload['sub']];
            if (! $user->avatar && ! empty($payload['picture'])) {
                $updates['avatar'] = $payload['picture'];
            }
            if (! $user->email_verified_at && strcasecmp($user->email, $email) === 0) {
                $updates['email_verified_at'] = now();
            }
            $user->update($updates);

            // Existing accounts retain their roles and approval state.
            return $user;
        }, 3);
    }
}
