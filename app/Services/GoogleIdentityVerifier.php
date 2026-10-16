<?php

namespace App\Services;

use Google\Client;
use Illuminate\Validation\ValidationException;

class GoogleIdentityVerifier
{
    public function verify(string $credential): array
    {
        $clientId = config('services.google.client_id');
        if (! $clientId) {
            throw ValidationException::withMessages(['credential' => 'Google sign-in is unavailable. Use your email and password.']);
        }
        try {
            $payload = (new Client(['client_id' => $clientId]))->verifyIdToken($credential);
        } catch (\Throwable $exception) {
            $payload = false;
        }
        if (! $payload) {
            throw ValidationException::withMessages(['credential' => 'Invalid or expired Google credential.']);
        }

        return $payload;
    }
}
