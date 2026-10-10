<?php

namespace App\Http\Middleware;

use App\Models\SiteVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class RecordPublicVisit
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if ($request->isMethod('GET') && $response->isSuccessful() && ! $request->expectsJson()) {
            try {
                $hash = fn ($value) => hash_hmac('sha256', (string) $value, (string) config('app.key'));
                $session = $hash($request->session()->getId());
                if (RateLimiter::attempt('blade-visit:'.$session.':'.$request->path(), 1, fn () => true, 1800)) {
                    SiteVisit::create(['path' => '/'.ltrim($request->path(), '/'), 'url' => $request->url(),
                        'visitor_id_hash' => $session, 'session_id_hash' => $session, 'ip_hash' => $hash($request->ip()),
                        'user_agent_hash' => $hash($request->userAgent()), 'occurred_at' => now()]);
                }
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return $response;
    }
}
