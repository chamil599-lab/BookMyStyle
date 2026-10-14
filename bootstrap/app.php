<?php

use App\Http\Middleware\AcceptBearerTokenHeader;
use App\Http\Middleware\CheckSubscription;
use App\Http\Middleware\ConfiguredTrustProxies;
use App\Http\Middleware\EnsureSubscriptionActive;
use App\Http\Middleware\EnsureUserIsApproved;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [AuthenticateSession::class]);
        $middleware->replace(
            TrustProxies::class,
            ConfiguredTrustProxies::class
        );

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'approved' => EnsureUserIsApproved::class,
            'subscription' => CheckSubscription::class,
            'subscription.active' => EnsureSubscriptionActive::class,
        ]);

        $middleware->api(prepend: [
            AcceptBearerTokenHeader::class,
        ]);

        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->isMethod('OPTIONS')) {
                return null;
            }

            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            return route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Authentication failures are rendered outside route middleware.
        $exceptions->respond(function ($response, Throwable $exception, Request $request) {
            if ($request->is('api/auth/*')) {
                $response->headers->set('Cache-Control', 'private, no-store');
                $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
            }

            return $response;
        });
    })->create();
