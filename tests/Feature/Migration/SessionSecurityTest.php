<?php

use App\Models\User;
use Illuminate\Session\DatabaseSessionHandler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

it('emits an encrypted host-only HttpOnly SameSite session cookie under HTTPS configuration', function () {
    config(['session.secure' => true, 'session.http_only' => true, 'session.same_site' => 'lax', 'session.domain' => null]);
    $response = $this->get('https://localhost/login')->assertOk();
    $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));
    expect($cookie)->not->toBeNull()->and($cookie->isSecure())->toBeTrue()->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('lax')->and($cookie->getDomain())->toBeNull()
        ->and($cookie->getValue())->not->toBe(Session::getId());
});

it('ignores a supplied session ID in URL parameters when authenticating browser requests', function () {
    $this->get('/profile?'.http_build_query([config('session.cookie') => str_repeat('a', 40), 'user_id' => 1]))
        ->assertRedirect(route('login'));
    $this->assertGuest('web');
    expect(Session::getId())->not->toBe(str_repeat('a', 40));
});

it('expires database session payloads on the server after the idle lifetime', function () {
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'expiry-fixture', 'user_id' => $user->id,
        'payload' => base64_encode('private-fixture'), 'last_activity' => now()->subMinutes(121)->timestamp]);
    $handler = new DatabaseSessionHandler(DB::connection(), 'sessions', 120, app());
    expect($handler->read('expiry-fixture'))->toBe('');
    DB::table('sessions')->where('id', 'expiry-fixture')->update(['last_activity' => now()->timestamp]);
    expect($handler->read('expiry-fixture'))->toBe('private-fixture');
});

it('destroys the old database session record and session data on logout', function () {
    config(['session.driver' => 'database']);
    $user = User::factory()->create();
    $this->actingAs($user, 'web')->withSession(['private_fixture' => 'old-session-data'])->get('/profile')->assertOk();
    $oldId = Session::getId();
    expect(DB::table('sessions')->where('id', $oldId)->exists())->toBeTrue();
    $this->withCookie(config('session.cookie'), $oldId)->post('/logout')
        ->assertRedirect('/')->assertSessionMissing('private_fixture');
    expect(DB::table('sessions')->where('id', $oldId)->exists())->toBeFalse()->and(Session::getId())->not->toBe($oldId);
    $this->assertGuest('web');
});
