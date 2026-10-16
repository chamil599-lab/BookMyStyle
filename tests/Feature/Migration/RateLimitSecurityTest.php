<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

beforeEach(function () {
    Notification::fake();
});

it('blocks the sixth API login attempt and permits retry after cooldown', function () {
    $data = ['email' => 'missing@example.test', 'password' => 'wrong'];
    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/auth/login', $data)->assertUnprocessable();
    }
    $this->postJson('/api/auth/login', $data)->assertStatus(429)->assertHeader('Retry-After');
    $this->travel(61)->seconds();
    $this->postJson('/api/auth/login', $data)->assertUnprocessable();
});

it('caps API login attempts even when email addresses rotate', function () {
    for ($i = 0; $i < 30; $i++) {
        $this->postJson('/api/auth/login', ['email' => 'missing'.$i.'@example.test', 'password' => 'wrong'])->assertUnprocessable();
    }
    $this->postJson('/api/auth/login', ['email' => 'another@example.test', 'password' => 'wrong'])->assertStatus(429);
});

it('limits public API attempts before validation and database writes', function ($path) {
    for ($i = 0; $i < 10; $i++) {
        $this->postJson($path, [])->assertUnprocessable();
    }
    $this->postJson($path, [])->assertStatus(429);
})->with(['/api/auth/register', '/api/auth/google', '/api/auth/forgot-password', '/api/auth/reset-password', '/api/bookings']);

it('shares current-password guessing limits across API and browser password forms', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'web');
    $data = ['current_password' => 'wrong', 'password' => 'new-secure-password', 'password_confirmation' => 'new-secure-password'];
    for ($i = 0; $i < 5; $i++) {
        Volt::test('profile.update-password-form')->set('current_password', 'wrong')
            ->set('password', $data['password'])->set('password_confirmation', $data['password'])
            ->call('updatePassword')->assertHasErrors('current_password');
    }
    $this->postJson('/api/auth/change-password', $data)->assertUnprocessable()
        ->assertJsonValidationErrors('current_password')->assertSee('Too many attempts');
    Volt::test('settings.password')->set('current_password', 'password')
        ->set('password', $data['password'])->set('password_confirmation', $data['password'])
        ->call('updatePassword')->assertHasErrors('current_password')->assertSee('Too many attempts');
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    foreach (['profile.delete-user-form', 'settings.delete-user-form'] as $form) {
        Volt::test($form)->set('password', 'password')->call('deleteUser')
            ->assertHasErrors('password')->assertSee('Too many attempts');
    }
    expect($user->fresh())->not->toBeNull();
});
