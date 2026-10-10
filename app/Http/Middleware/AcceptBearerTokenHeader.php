<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AcceptBearerTokenHeader
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->headers->has('Authorization') && $request->headers->has('X-Authorization')) {
            $request->headers->set('Authorization', $request->headers->get('X-Authorization'));
            $request->server->set('HTTP_AUTHORIZATION', $request->headers->get('X-Authorization'));
        }

        return $next($request);
    }
}
