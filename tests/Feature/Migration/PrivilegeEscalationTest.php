<?php

use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Models\User;
use App\Services\UserModeration;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    foreach (['admin', 'owner', 'customer'] as $role) {
        Role::findOrCreate($role, 'web');
    }
});

it('rejects public elevated role registration', function ($role) {
    $this->postJson('/api/auth/register', ['name' => 'Escalation fixture', 'email' => 'escalation@example.test',
        'password' => 'secure-password', 'password_confirmation' => 'secure-password', 'role' => $role,
        'is_approved' => true, 'roles' => ['admin']])->assertUnprocessable();
    expect(User::where('email', 'escalation@example.test')->exists())->toBeFalse();
})->with(['admin', 'super_admin', ['admin']]);

it('ignores forged privilege fields in API profile updates', function ($role) {
    $user = User::factory()->create(['is_approved' => false]);
    $user->assignRole($role);
    Sanctum::actingAs($user);
    $this->putJson('/api/customer/profile', ['name' => 'Updated fixture', 'email' => $user->email,
        'role' => 'admin', 'roles' => ['admin'], 'permissions' => ['*'], 'is_approved' => true,
        'approved_by' => $user->id])->assertOk();
    expect($user->fresh()->getRoleNames()->all())->toBe([$role])
        ->and($user->fresh()->is_approved)->toBeFalse()->and($user->fresh()->approved_by)->toBeNull();
})->with(['customer', 'owner']);

it('denies lower role access to role management and user creation components', function ($role) {
    $user = User::factory()->create(['is_approved' => true]);
    $user->assignRole($role);
    $this->actingAs($user, 'web');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->get('/filament/shield/roles')->assertForbidden();
    Livewire::test(CreateUser::class)->assertForbidden();
    expect(User::count())->toBe(1);
})->with(['customer', 'owner']);

it('denies self approval through the shared moderation service', function ($role) {
    $user = User::factory()->create(['is_approved' => false]);
    $user->assignRole($role);
    expect(fn () => app(UserModeration::class)->approve($user, $user, 'owner'))->toThrow(AuthorizationException::class);
    expect($user->fresh()->is_approved)->toBeFalse()->and($user->fresh()->getRoleNames()->all())->toBe([$role]);
})->with(['customer', 'owner']);

it('rejects a stale administrator actor after its role is revoked', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->load('roles');
    $target = User::factory()->create(['is_approved' => false]);
    $target->assignRole('owner');
    User::findOrFail($admin->id)->syncRoles(['customer']);
    expect(fn () => app(UserModeration::class)->approve($admin, $target, 'owner'))->toThrow(AuthorizationException::class);
    expect($target->fresh()->is_approved)->toBeFalse()->and($target->fresh()->approved_by)->toBeNull();
});
