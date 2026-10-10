<?php

namespace App\Services;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthActionLimiter
{
    public function check(string $action, ?string $email = null, int $maximum = 10, string $errorField = 'email'): void
    {
        $keys = ['blade-auth:'.$action.':ip:'.request()->ip() => $email ? 30 : $maximum];
        if ($email !== null) {
            $keys['blade-auth:'.$action.':identity:'.hash('sha256', strtolower(trim($email)).'|'.request()->ip())] = $maximum;
        }
        foreach ($keys as $key => $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                throw ValidationException::withMessages([$errorField => 'Too many attempts. Please try again in a minute.']);
            }
        }
        foreach ($keys as $key => $limit) {
            RateLimiter::hit($key, 60);
        }
    }
}
