<?php

use App\Http\Middleware\SecurityResponseHeaders;
use Illuminate\Http\Request;

it('protects public auth API errors and panel responses with baseline browser headers', function ($path) {
    $response = $this->get($path);
    $response->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    expect($response->headers->get('Content-Security-Policy'))->toContain("base-uri 'self'", "object-src 'none'", "frame-ancestors 'self'", "form-action 'self'");
})->with(['/', '/login', '/api/auth/me', '/missing-security-fixture', '/filament/login']);

it('adds HSTS only to production HTTPS responses', function () {
    $this->app['env'] = 'production';
    $https = Request::create('https://example.test/');
    expect(SecurityResponseHeaders::apply($https, response('fixture'))->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000');
    expect(SecurityResponseHeaders::apply(Request::create('http://example.test/'), response('fixture'))->headers->has('Strict-Transport-Security'))->toBeFalse();
    $this->app['env'] = 'local';
    expect(SecurityResponseHeaders::apply($https, response('fixture'))->headers->has('Strict-Transport-Security'))->toBeFalse();
});

it('preserves stronger per-response content policies', function () {
    $policy = "default-src 'none'; sandbox";
    $response = SecurityResponseHeaders::apply(Request::create('/'), response('fixture')->header('Content-Security-Policy', $policy));
    expect($response->headers->get('Content-Security-Policy'))->toBe($policy);
});
