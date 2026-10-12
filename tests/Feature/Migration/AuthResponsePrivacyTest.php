<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('prevents caching authenticated API identity responses', function () {
    Sanctum::actingAs(User::factory()->create());
    $response = $this->getJson('/api/auth/me')->assertSuccessful();
    expect($response->headers->get('Cache-Control'))->toContain('private', 'no-store');
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('prevents caching API login validation responses', function () {
    $response = $this->postJson('/api/auth/login', [])->assertUnprocessable();
    expect($response->headers->get('Cache-Control'))->toContain('private', 'no-store');
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});
