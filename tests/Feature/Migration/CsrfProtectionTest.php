<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('admin', 'web');
    $user = User::factory()->create();
    $user->assignRole('admin');
    $this->actingAs($user);
    // Laravel normally bypasses CSRF in tests. Exercise the real middleware.
    $this->app['env'] = 'production';
});

it('rejects missing and mismatched tokens on browser mutations', function (string $method, string $uri) {
    $this->withSession(['_token' => str_repeat('a', 40)]);
    $this->call($method, $uri)->assertStatus(419);
    $this->call($method, $uri, ['_token' => str_repeat('b', 40)])->assertStatus(419);
})->with([
    ['POST', '/logout'],
    ['POST', '/auth/google'],
    ['POST', '/dashboard/bookings/1/cancel'],
    ['DELETE', '/dashboard/favorites/1'],
    ['POST', '/salons/1/favorite'],
    ['DELETE', '/salons/1/favorite'],
    ['POST', '/dashboard/notifications/read-all'],
    ['POST', '/dashboard/notifications/11111111-1111-4111-8111-111111111111/read'],
    ['DELETE', '/dashboard/notifications/11111111-1111-4111-8111-111111111111'],
    ['DELETE', '/dashboard/notifications'],
    ['POST', '/subscription/subscribe/1'],
    ['POST', '/livewire/update'],
    ['POST', '/livewire/upload-file'],
    ['POST', '/filament/logout'],
    ['POST', '/owner/salons'],
    ['PUT', '/owner/salons/1'],
]);

it('accepts a matching form token and logs out the session', function () {
    $token = str_repeat('a', 40);
    $this->withSession(['_token' => $token])->post('/logout', ['_token' => $token])->assertRedirect('/');
    $this->assertGuest();
});

it('accepts a matching AJAX header token and rejects a mismatched header', function () {
    $token = str_repeat('a', 40);
    $this->withSession(['_token' => $token])->withHeader('X-CSRF-TOKEN', str_repeat('b', 40))
        ->post('/logout')->assertStatus(419);
    $this->withHeader('X-CSRF-TOKEN', $token)->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

it('does not allow favorite changes through GET requests', function () {
    $this->get('/salons/1/favorite')->assertStatus(405);
});

it('keeps the bearer API outside the browser session middleware pipeline', function () {
    $route = app('router')->getRoutes()->match(Request::create('/api/auth/logout', 'POST'));
    $middleware = app('router')->gatherRouteMiddleware($route);
    expect($middleware)->not->toContain(StartSession::class)
        ->not->toContain(EnsureFrontendRequestsAreStateful::class);
});
