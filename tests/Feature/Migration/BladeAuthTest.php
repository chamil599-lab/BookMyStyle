<?php

use App\Models\User;
use App\Services\GoogleIdentityVerifier;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\URL;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Notification::fake();
    foreach (['admin', 'owner', 'customer'] as $role) {
        Role::findOrCreate($role, 'web');
    }
});

it('ignores forwarded identity and scheme when no proxy is configured even for a cloud-looking host', function () {
    config(['app.trusted_proxies' => []]);
    $request = \Illuminate\Http\Request::create('http://example.on-forge.com/login', server: [
        'REMOTE_ADDR' => '192.0.2.10', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ]);
    (new \App\Http\Middleware\ConfiguredTrustProxies)->handle($request, function ($request) {
        expect($request->ip())->toBe('192.0.2.10')->and($request->isSecure())->toBeFalse();

        return response('ok');
    });
});

it('accepts forwarded identity only from the configured proxy', function () {
    config(['app.trusted_proxies' => ['192.0.2.10']]);
    $request = \Illuminate\Http\Request::create('http://example.test/login', server: [
        'REMOTE_ADDR' => '192.0.2.10', 'HTTP_X_FORWARDED_FOR' => '198.51.100.20',
        'HTTP_X_FORWARDED_PROTO' => 'https',
    ]);
    try {
        (new \App\Http\Middleware\ConfiguredTrustProxies)->handle($request, function ($request) {
            expect($request->ip())->toBe('198.51.100.20')->and($request->isSecure())->toBeTrue();

            return response('ok');
        });
    } finally {
        \Illuminate\Http\Request::setTrustedProxies([], 0);
    }
});

it('registers a customer with verified-email delivery but no API token', function () {
    Volt::test('pages.auth.register')->set('name', 'Customer')->set('email', 'Customer@example.com')
        ->set('password', 'secure-password')->set('password_confirmation', 'secure-password')
        ->call('register')->assertHasNoErrors();
    $user = User::where('email', 'customer@example.com')->firstOrFail();
    expect($user->hasRole('customer'))->toBeTrue()->and($user->is_approved)->toBeTrue()
        ->and($user->email_verified_at)->toBeNull()->and($user->tokens()->count())->toBe(0);
    Notification::assertSentTo($user, VerifyEmail::class);
    $this->get('/dashboard')->assertRedirect(route('verification.notice'));
});

it('registers an owner as pending and rejects public administrator registration', function () {
    Volt::test('pages.auth.register')->set('name', 'Owner')->set('email', 'owner@example.com')->set('role', 'owner')
        ->set('password', 'secure-password')->set('password_confirmation', 'secure-password')
        ->call('register')->assertHasNoErrors();
    $owner = User::where('email', 'owner@example.com')->firstOrFail();
    expect($owner->hasRole('owner'))->toBeTrue()->and($owner->is_approved)->toBeFalse();
    $this->postJson('/api/auth/register', ['name' => 'Intruder', 'email' => 'admin@example.com', 'role' => 'admin',
        'password' => 'secure-password', 'password_confirmation' => 'secure-password'])->assertUnprocessable();
    expect(User::where('email', 'admin@example.com')->exists())->toBeFalse();
});

it('ignores approval and verification fields submitted to API registration', function () {
    $this->postJson('/api/auth/register', ['name' => 'Owner', 'email' => 'owner@example.com', 'role' => 'owner',
        'password' => 'secure-password', 'password_confirmation' => 'secure-password', 'is_approved' => true,
        'email_verified_at' => now()->toIso8601String()])->assertCreated();
    $owner = User::where('email', 'owner@example.com')->firstOrFail();
    expect($owner->is_approved)->toBeFalse()->and($owner->email_verified_at)->toBeNull();
});

it('rotates the session identifier when logging in', function () {
    $user = User::factory()->create();
    Session::start();
    $previous = Session::getId();
    Volt::test('pages.auth.login')->set('form.email', strtoupper($user->email))->set('form.password', 'password')
        ->call('login')->assertHasNoErrors();
    expect(Session::getId())->not->toBe($previous);
    $this->assertAuthenticatedAs($user);
});

it('lets a pending owner sign in to a restricted account page without gaining management access', function () {
    $owner = User::factory()->create(['is_approved' => false]);
    $owner->assignRole('owner');
    Volt::test('pages.auth.login')->set('form.email', $owner->email)->set('form.password', 'password')
        ->call('login')->assertHasNoErrors();
    $this->get('/dashboard')->assertOk()->assertSee('awaiting admin approval');
    $this->getJson('/owner/salons/create')->assertForbidden();
});

it('routes verified administrators and approved owners to their respective Filament dashboards', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/dashboard')->assertRedirect(route('filament.admin.pages.dashboard'));
    $owner = User::factory()->create(['is_approved' => true]);
    $owner->assignRole('owner');
    $this->actingAs($owner)->get('/dashboard')->assertRedirect(route('filament.admin.pages.owner-dashboard'));
});

it('invalidates the session and CSRF token on logout', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/profile')->assertOk();
    $oldId = Session::getId();
    $oldToken = Session::token();
    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
    expect(Session::getId())->not->toBe($oldId)->and(Session::token())->not->toBe($oldToken);
});

it('requires CSRF protection for web logout and Google sign-in', function () {
    $this->app['env'] = 'production';
    $this->post('/logout')->assertStatus(419);
    $this->post('/auth/google', ['credential' => 'untrusted'])->assertStatus(419);
});

it('throttles repeated Livewire login attempts', function () {
    for ($attempt = 0; $attempt < 6; $attempt++) {
        $component = Volt::test('pages.auth.login')->set('form.email', 'missing@example.com')
            ->set('form.password', 'wrong')->call('login')->assertHasErrors(['form.email']);
    }
    $component->assertSee('Too many login attempts');
});

it('throttles repeated verification resends', function () {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user);
    for ($attempt = 0; $attempt < 3; $attempt++) {
        Volt::test('pages.auth.verify-email')->call('sendVerification')->assertHasNoErrors();
    }
    Volt::test('pages.auth.verify-email')->call('sendVerification')->assertHasErrors(['email']);
    Notification::assertSentToTimes($user, VerifyEmail::class, 3);
});

it('rejects expired or unsigned verification links and links belonging to another account', function () {
    $user = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();
    $this->actingAs($user);
    $this->get(URL::temporarySignedRoute('verification.verify', now()->subMinute(), ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();
    $this->get(route('verification.verify', ['id' => $user->id, 'hash' => sha1($user->email)]))->assertForbidden();
    $this->get(URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $other->id, 'hash' => sha1($other->email)]))->assertForbidden();
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse()->and($other->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('rejects verification links after a profile email change', function () {
    $user = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(10), ['id' => $user->id, 'hash' => sha1($user->email)]);
    $user->update(['email' => 'changed@example.com']);
    $this->actingAs($user)->get($url)->assertForbidden();
});

it('returns the same reset-link response for known and unknown email addresses', function () {
    $user = User::factory()->create();
    $known = $this->postJson('/api/auth/forgot-password', ['email' => $user->email])->assertOk()->json();
    $unknown = $this->postJson('/api/auth/forgot-password', ['email' => 'missing@example.com'])->assertOk()->json();
    expect($known)->toBe($unknown);
    Volt::test('pages.auth.forgot-password')->set('email', 'missing@example.com')->call('sendPasswordResetLink')->assertHasNoErrors();
});

it('revokes API tokens and rotates remember credentials when resetting a password', function () {
    $user = User::factory()->create(['remember_token' => 'old-remember-token']);
    $user->createToken('old-device');
    $token = Password::createToken($user);
    Volt::test('pages.auth.reset-password', ['token' => $token])->set('email', $user->email)
        ->set('password', 'new-secure-password')->set('password_confirmation', 'new-secure-password')
        ->call('resetPassword')->assertHasNoErrors();
    expect(Hash::check('new-secure-password', $user->fresh()->password))->toBeTrue()
        ->and($user->tokens()->count())->toBe(0)->and($user->fresh()->remember_token)->not->toBe('old-remember-token');
    Volt::test('pages.auth.reset-password', ['token' => $token])->set('email', $user->email)
        ->set('password', 'another-password')->set('password_confirmation', 'another-password')
        ->call('resetPassword')->assertHasErrors(['email']);
});

it('rejects stale authenticated session hashes after a password change', function () {
    $user = User::factory()->create();
    $previous = $user->password;
    $user->update(['password' => 'new-secure-password']);
    $this->actingAs($user)->withSession(['password_hash_web' => $previous])->get('/profile')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('removes database sessions on a password change', function () {
    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'old-device', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);
    config(['session.driver' => 'database']);
    $user->update(['password' => 'new-secure-password']);
    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);
});

it('protects administrator and owner accounts from customer self-deletion', function ($role) {
    $user = User::factory()->create();
    $user->assignRole($role);
    $this->actingAs($user);
    Volt::test('profile.delete-user-form')->set('password', 'password')->call('deleteUser')->assertForbidden();
    expect($user->fresh())->not->toBeNull();
})->with(['admin', 'owner']);

it('accepts valid Google identity as a session without exposing a bearer token', function () {
    $this->mock(GoogleIdentityVerifier::class)->shouldReceive('verify')->once()->andReturn([
        'sub' => 'google-one', 'email' => 'google@gmail.com', 'email_verified' => true, 'name' => 'Google User']);
    $this->post('/auth/google', ['credential' => 'verified-on-server'])->assertRedirect(route('dashboard'));
    $user = User::where('google_id', 'google-one')->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->hasVerifiedEmail())->toBeTrue()->and($user->hasRole('customer'))->toBeTrue()->and($user->tokens()->count())->toBe(0);
});

it('requires a verified Google email', function () {
    $this->mock(GoogleIdentityVerifier::class)->shouldReceive('verify')->andReturn([
        'sub' => 'unverified', 'email' => 'unverified@gmail.com', 'email_verified' => false]);
    $this->postJson('/api/auth/google', ['credential' => 'bad'])->assertUnprocessable();
    expect(User::count())->toBe(0);
});

it('does not link a Google identity when subject and email resolve to different accounts', function () {
    $first = User::factory()->create(['google_id' => 'google-one']);
    $second = User::factory()->create(['email' => 'second@gmail.com']);
    $this->mock(GoogleIdentityVerifier::class)->shouldReceive('verify')->andReturn([
        'sub' => 'google-one', 'email' => $second->email, 'email_verified' => true]);
    $this->postJson('/api/auth/google', ['credential' => 'conflict'])->assertUnprocessable();
    expect($second->fresh()->google_id)->toBeNull()->and($first->fresh()->google_id)->toBe('google-one');
});

it('does not link an existing password account using a third-party Google email claim alone', function () {
    $user = User::factory()->create(['email' => 'external@example.com']);
    $this->mock(GoogleIdentityVerifier::class)->shouldReceive('verify')->andReturn([
        'sub' => 'external-google', 'email' => $user->email, 'email_verified' => true]);
    $this->postJson('/api/auth/google', ['credential' => 'external'])->assertUnprocessable();
    expect($user->fresh()->google_id)->toBeNull();
});

it('preserves existing Google-account roles despite a requested owner role', function () {
    $user = User::factory()->create(['email' => 'customer@gmail.com', 'google_id' => 'existing']);
    $user->assignRole('customer');
    $this->mock(GoogleIdentityVerifier::class)->shouldReceive('verify')->andReturn([
        'sub' => 'existing', 'email' => $user->email, 'email_verified' => true]);
    $this->postJson('/api/auth/google', ['credential' => 'valid', 'role' => 'owner'])->assertOk();
    expect($user->fresh()->hasRole('owner'))->toBeFalse()->and($user->fresh()->hasRole('customer'))->toBeTrue();
});

it('keeps legacy query-string reset links on the backend', function () {
    $this->get('/password/reset?token=legacy-token&email=user%40example.com')
        ->assertRedirect(route('password.reset', ['token' => 'legacy-token', 'email' => 'user@example.com']));
});

it('shares the web session with Filament while keeping customers out of the panel', function () {
    $customer = User::factory()->create();
    $customer->assignRole('customer');
    $this->actingAs($customer)->get(route('filament.admin.pages.dashboard'))->assertForbidden();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get(route('filament.admin.pages.dashboard'))->assertOk();
});

it('allows pending owners to reach email verification without an approval redirect loop', function () {
    $owner = User::factory()->unverified()->create(['is_approved' => false]);
    $owner->assignRole('owner');
    $this->actingAs($owner)->get(route('filament.admin.auth.email-verification.prompt'))->assertOk();
});
