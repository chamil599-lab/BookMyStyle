<?php

use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use App\Services\GoogleIdentityVerifier;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\TransientToken;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(now()->startOfSecond());
    Notification::fake();
    config(['sanctum.expiration' => 1440]);
    Role::findOrCreate('customer', 'web');
    $this->customer = User::factory()->create(['password' => 'Fixture-Password-123!']);
    $this->customer->assignRole('customer');
});

it('issues bounded login tokens without changing the response shape', function () {
    $response = $this->postJson('/api/auth/login', ['email' => $this->customer->email, 'password' => 'Fixture-Password-123!'])->assertSuccessful();
    expect($response->json('token'))->toBeString();
    $token = $this->customer->tokens()->firstOrFail();
    expect($token->expires_at->equalTo(now()->addDay()))->toBeTrue();
    $response->assertJsonPath('user.id', $this->customer->id);
});

it('issues bounded registration and Google tokens', function () {
    $this->postJson('/api/auth/register', ['name' => 'Fixture Customer', 'email' => 'new-token-fixture@example.test',
        'password' => 'Fixture-Password-123!', 'password_confirmation' => 'Fixture-Password-123!', 'role' => 'customer'])->assertCreated();
    expect(User::where('email', 'new-token-fixture@example.test')->firstOrFail()->tokens()->firstOrFail()->expires_at->equalTo(now()->addDay()))->toBeTrue();
    $this->mock(GoogleIdentityVerifier::class, function ($mock) {
        $mock->shouldReceive('verify')->once()->andReturn(['sub' => 'fixture-google-token-id', 'email' => 'fixture-token@gmail.com', 'email_verified' => true]);
    });
    $this->postJson('/api/auth/google', ['credential' => 'synthetic-not-a-google-token'])->assertCreated();
    expect(User::where('email', 'fixture-token@gmail.com')->firstOrFail()->tokens()->firstOrFail()->expires_at->equalTo(now()->addDay()))->toBeTrue();
});

it('rejects legacy tokens by age without deleting their records', function () {
    $issued = $this->customer->createToken('legacy-no-expiry');
    $issued->accessToken->forceFill(['created_at' => now()->subHours(25)])->save();
    $this->withToken($issued->plainTextToken)->getJson('/api/auth/me')->assertUnauthorized();
    expect($issued->accessToken->fresh()->expires_at)->toBeNull()
        ->and($this->customer->tokens()->count())->toBe(1);
});

it('rejects explicit expiry and accepts a fresh bearer token', function () {
    $expired = $this->customer->createToken('expired', ['*'], now()->subMinute());
    $this->withToken($expired->plainTextToken)->getJson('/api/auth/me')->assertUnauthorized();
    $this->app['auth']->forgetGuards();
    $fresh = $this->customer->createToken('fresh', ['*'], now()->addDay());
    $this->withToken($fresh->plainTextToken)->getJson('/api/auth/me')->assertSuccessful();
    $this->travel(25)->hours();
    $this->app['auth']->forgetGuards();
    $this->withToken($fresh->plainTextToken)->getJson('/api/auth/me')->assertUnauthorized();
});

it('revokes only the current bearer token on logout', function () {
    $current = $this->customer->createToken('current');
    $other = $this->customer->createToken('other-device');
    $this->withToken($current->plainTextToken)->postJson('/api/auth/logout')->assertSuccessful();
    $this->app['auth']->forgetGuards();
    $this->withToken($current->plainTextToken)->getJson('/api/auth/me')->assertUnauthorized();
    $this->app['auth']->forgetGuards();
    $this->withToken($other->plainTextToken)->getJson('/api/auth/me')->assertSuccessful();
    expect($this->customer->tokens()->count())->toBe(1);
});

it('logs out a session-authenticated caller without deleting unrelated bearer tokens', function () {
    $this->customer->createToken('other-device');
    $this->customer->withAccessToken(new TransientToken);
    Route::middleware(['web', 'auth:web'])->post('/api/auth/session-logout-fixture', [AuthController::class, 'logout']);
    $this->actingAs($this->customer, 'web')->postJson('/api/auth/session-logout-fixture')->assertSuccessful();
    $this->assertGuest('web');
    expect($this->customer->tokens()->count())->toBe(1);
});

it('revokes all bearer tokens after a successful API password change', function () {
    $current = $this->customer->createToken('current');
    $this->customer->createToken('other-device');
    $this->withToken($current->plainTextToken)->postJson('/api/auth/change-password', ['current_password' => 'Fixture-Password-123!',
        'password' => 'Changed-Fixture-Password-456!', 'password_confirmation' => 'Changed-Fixture-Password-456!'])->assertSuccessful();
    expect($this->customer->tokens()->count())->toBe(0);
    $this->app['auth']->forgetGuards();
    $this->withToken($current->plainTextToken)->getJson('/api/auth/me')->assertUnauthorized();
});

it('revokes bearer tokens after an API password reset', function () {
    $this->customer->createToken('existing');
    $reset = Password::createToken($this->customer);
    $this->postJson('/api/auth/reset-password', ['token' => $reset, 'email' => $this->customer->email,
        'password' => 'Reset-Fixture-Password-456!', 'password_confirmation' => 'Reset-Fixture-Password-456!'])->assertSuccessful();
    expect($this->customer->tokens()->count())->toBe(0);
});

it('bounds token TTL configuration so zero cannot disable expiry', function () {
    $name = 'SANCTUM_TOKEN_TTL_MINUTES';
    $previous = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null, getenv($name)];
    try {
        foreach (['0' => 1, '-10' => 1, 'invalid' => 1, '999999' => 43200, '60' => 60] as $value => $expected) {
            $_ENV[$name] = $_SERVER[$name] = (string) $value;
            putenv($name.'='.$value);
            $config = require config_path('sanctum.php');
            expect($config['expiration'])->toBe($expected);
        }
    } finally {
        [$environment, $server, $process] = $previous;
        if ($environment === null) {
            unset($_ENV[$name]);
        } else {
            $_ENV[$name] = $environment;
        }
        if ($server === null) {
            unset($_SERVER[$name]);
        } else {
            $_SERVER[$name] = $server;
        }
        putenv($process === false ? $name : $name.'='.$process);
    }
});
