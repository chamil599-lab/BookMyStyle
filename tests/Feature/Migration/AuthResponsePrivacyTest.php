<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;

it('hides internal exception details from API errors when debug mode is disabled', function () {
    config(['app.debug' => false]);
    Route::get('/api/security-error-fixture', function () {
        throw new RuntimeException('synthetic-private-error-detail');
    });
    $this->withExceptionHandling()->getJson('/api/security-error-fixture')->assertStatus(500)
        ->assertDontSee('synthetic-private-error-detail', false)->assertJsonMissingPath('trace')
        ->assertJsonMissingPath('file')->assertJsonMissingPath('exception');
});

it('prevents caching unauthorized API identity responses', function () {
    $response = $this->getJson('/api/auth/me')->assertUnauthorized();
    expect($response->headers->get('Cache-Control'))->toContain('private', 'no-store');
    $response->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

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
