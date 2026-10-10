<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: config('app.trusted_proxies', []), headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'approved' => \App\Http\Middleware\EnsureUserIsApproved::class,
            'subscription' => \App\Http\Middleware\CheckSubscription::class,
            'subscription.active' => \App\Http\Middleware\EnsureSubscriptionActive::class,
        ]);

        $middleware->api(prepend: [
            \App\Http\Middleware\AcceptBearerTokenHeader::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->isMethod('OPTIONS')) {
                return null;
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return route('filament.admin.auth.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
