<?php

namespace App\Http\Middleware;

use App\Support\PublicUrl;
use Closure;
use Illuminate\Http\Request;

class RestrictBrowserRedirects
{
    public function handle(Request $request, Closure $next)
    {
        $isLocal = static function (mixed $url): bool {
            if (! is_string($url) || ! PublicUrl::link($url)) {
                return false;
            }
            if (str_starts_with($url, '/')) {
                return true;
            }
            $parts = parse_url($url);
            $origin = parse_url((string) config('app.url'));

            return ! isset($parts['user']) && ! isset($parts['pass'])
                && strtolower($parts['scheme'] ?? '') === strtolower($origin['scheme'] ?? '')
                && strtolower($parts['host'] ?? '') === strtolower($origin['host'] ?? '')
                && ($parts['port'] ?? (strtolower($parts['scheme'] ?? '') === 'https' ? 443 : 80))
                    === ($origin['port'] ?? (strtolower($origin['scheme'] ?? '') === 'https' ? 443 : 80));
        };

        foreach (['url.intended', '_previous.url'] as $key) {
            if ($request->session()->has($key) && ! $isLocal($request->session()->get($key))) {
                $request->session()->forget($key);
            }
        }
        $response = $next($request);
        if ($response->isRedirection() && $response->headers->has('Location') && ! $isLocal($response->headers->get('Location'))) {
            $response->headers->set('Location', '/');
        }

        return $response;
    }
}
