<?php

namespace App\Services;

use App\Models\User;
use Laravel\Sanctum\NewAccessToken;

final class ApiTokenService
{
    public function issue(User $user): NewAccessToken
    {
        $minutes = max(1, min(43200, (int) (config('sanctum.expiration') ?? 1440)));

        return $user->createToken('auth-token', ['*'], now()->addMinutes($minutes));
    }
}
