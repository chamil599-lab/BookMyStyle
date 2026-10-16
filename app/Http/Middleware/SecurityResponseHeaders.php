<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityResponseHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        return self::apply($request, $next($request));
    }

    public static function apply(Request $request, Response $response): Response
    {
        foreach ([
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'X-XSS-Protection' => '0',
            // Resource/script restrictions require a separate nonce/CSP-compatible Alpine rollout.
            'Content-Security-Policy' => "base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'",
        ] as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
