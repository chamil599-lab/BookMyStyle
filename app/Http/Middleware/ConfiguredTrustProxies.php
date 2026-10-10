<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

class ConfiguredTrustProxies extends TrustProxies
{
    public function handle(Request $request, Closure $next)
    {
        $this->proxies = config('app.trusted_proxies', []);
        $this->headers = Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB;

        if ($this->proxies === []) {
            $request::setTrustedProxies([], $this->headers);

            return $next($request);
        }

        return parent::handle($request, $next);
    }
}
