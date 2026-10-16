<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Notification::fake();
    Role::findOrCreate('customer', 'web');
    $this->user = User::factory()->create();
    $this->user->assignRole('customer');
    $this->resetData = fn ($token, $email = null) => ['token' => $token, 'email' => $email ?? $this->user->email,
        'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password'];
});

it('returns the same credential failure for unknown users and wrong passwords without issuing tokens', function () {
    $known = $this->postJson('/api/auth/login', ['email' => $this->user->email, 'password' => 'wrong-password'])->assertUnprocessable();
    $unknown = $this->postJson('/api/auth/login', ['email' => 'absent@example.test', 'password' => 'wrong-password'])->assertUnprocessable();
    expect($known->json())->toBe($unknown->json())->and($this->user->tokens()->count())->toBe(0);
    $this->assertGuest('web');
});

it('rejects wrong reset tokens and tokens belonging to a different email', function () {
    $other = User::factory()->create();
    $token = Password::createToken($other);
    foreach (['invalid-token', $token] as $candidate) {
        $this->postJson('/api/auth/reset-password', ($this->resetData)($candidate))->assertStatus(400);
    }
    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

it('rejects expired password reset tokens', function () {
    $token = Password::createToken($this->user);
    DB::table('password_reset_tokens')->where('email', $this->user->email)->update(['created_at' => now()->subMinutes(61)]);
    $this->postJson('/api/auth/reset-password', ($this->resetData)($token))->assertStatus(400);
    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

it('rejects malformed reset token types before calling the password broker', function () {
    $this->postJson('/api/auth/reset-password', ($this->resetData)(['unexpected']))
        ->assertUnprocessable()->assertJsonValidationErrors('token');
    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

it('consumes reset tokens once and revokes existing bearer access', function () {
    $oldToken = $this->user->createToken('fixture')->plainTextToken;
    $token = Password::createToken($this->user);
    $this->postJson('/api/auth/reset-password', ($this->resetData)($token))->assertOk();
    $this->postJson('/api/auth/reset-password', ($this->resetData)($token))->assertStatus(400);
    expect(Hash::check('new-secure-password', $this->user->fresh()->password))->toBeTrue()
        ->and($this->user->tokens()->count())->toBe(0);
    $this->withToken($oldToken)->getJson('/api/auth/me')->assertUnauthorized();
});

it('rejects password changes without the current password', function () {
    Sanctum::actingAs($this->user);
    $this->postJson('/api/auth/change-password', ['current_password' => 'wrong-password',
        'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password'])->assertStatus(400);
    expect(Hash::check('password', $this->user->fresh()->password))->toBeTrue();
});

it('fails closed when Google sign-in has no configured client ID', function () {
    config(['services.google.client_id' => null]);
    $this->postJson('/api/auth/google', ['credential' => 'untrusted-client-claim'])->assertUnprocessable();
    expect(User::count())->toBe(1)->and($this->user->tokens()->count())->toBe(0);
});
