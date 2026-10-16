<?php

use App\Filament\Admin\Pages\Auth\Register;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\AccountService;
use App\Services\GoogleIdentityVerifier;
use App\Services\SubscriptionService;
use App\Services\UserModeration;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    foreach (['owner', 'customer', 'admin'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->package = SubscriptionPackage::create(['name' => 'Pro trial fixture', 'slug' => 'pro-salon', 'price' => 100,
        'duration_days' => 30, 'max_salons' => 2, 'max_staff' => 3, 'max_services' => 10, 'is_active' => true]);
    $this->registration = ['name' => 'New owner', 'email' => 'trial-owner@example.test', 'password' => 'fixture-password',
        'password_confirmation' => 'fixture-password', 'role' => 'owner'];
});

it('creates a free ninety day trial atomically with an API owner registration', function () {
    $this->postJson('/api/auth/register', $this->registration + ['is_approved' => true])->assertCreated();
    $owner = User::where('email', $this->registration['email'])->firstOrFail();
    $trial = $owner->subscriptions()->sole();
    expect($trial->is_trial)->toBeTrue()->and($trial->status)->toBe('active')->and($trial->amount_paid)->toBe('0.00')
        ->and($trial->package_id)->toBe($this->package->id)->and((int) $trial->starts_at->diffInDays($trial->ends_at))->toBe(90)
        ->and($owner->is_approved)->toBeFalse()->and($owner->hasVerifiedEmail())->toBeFalse();
});

it('grants the same trial through native registration', function () {
    $component = Volt::test('pages.auth.register');
    foreach ($this->registration as $key => $value) {
        $component->set($key, $value);
    }
    $component->call('register')->assertHasNoErrors();
    expect(User::where('email', $this->registration['email'])->firstOrFail()->subscriptions()->where('is_trial', true)->count())->toBe(1);
});

it('creates a trial for a new Google owner without bypassing approval', function () {
    $this->mock(GoogleIdentityVerifier::class)->shouldReceive('verify')->andReturn([
        'sub' => 'new-trial-google', 'email' => 'trial-google@gmail.com', 'email_verified' => true]);
    $this->postJson('/api/auth/google', ['credential' => 'fixture', 'role' => 'owner'])->assertForbidden();
    $owner = User::where('google_id', 'new-trial-google')->firstOrFail();
    expect($owner->subscriptions()->where('is_trial', true)->count())->toBe(1)->and($owner->is_approved)->toBeFalse();
});

it('does not restart the trial on owner approval or repeat calls', function () {
    $owner = app(AccountService::class)->register($this->registration);
    $trial = $owner->subscriptions()->sole();
    expect(app(SubscriptionService::class)->startRegistrationTrial($owner))->toBeNull();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    app(UserModeration::class)->approve($admin, $owner);
    expect($owner->subscriptions()->count())->toBe(1)->and($trial->fresh()->ends_at->equalTo($trial->ends_at))->toBeTrue();
});

it('rolls back owner creation if the required trial package is unavailable', function () {
    $this->package->update(['is_active' => false]);
    expect(fn () => app(AccountService::class)->register($this->registration))->toThrow(ValidationException::class);
    expect(User::where('email', $this->registration['email'])->exists())->toBeFalse();
});

it('grants a trial through Filament owner registration', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Livewire::test(Register::class)
        ->fillForm(['name' => 'Panel owner', 'email' => 'panel-trial@example.test',
            'password' => 'fixture-password', 'passwordConfirmation' => 'fixture-password'])
        ->call('register')->assertHasNoFormErrors();
    $owner = User::where('email', 'panel-trial@example.test')->firstOrFail();
    expect($owner->subscriptions()->where('is_trial', true)->count())->toBe(1)->and($owner->is_approved)->toBeFalse();
});

it('grants a trial when an administrator creates a new owner', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin, 'web');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Managed owner', 'email' => 'managed-trial@example.test',
            'password' => 'fixture-password', 'role' => 'owner', 'is_approved' => true])
        ->call('create')->assertHasNoFormErrors();
    expect(User::where('email', 'managed-trial@example.test')->firstOrFail()->subscriptions()->where('is_trial', true)->count())->toBe(1);
});

it('does not grant customer accounts a salon trial or grant old owner accounts another trial', function () {
    $customer = app(AccountService::class)->register(array_replace($this->registration, ['role' => 'customer']));
    expect($customer->subscriptions()->count())->toBe(0);
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    expect(fn () => app(SubscriptionService::class)->startRegistrationTrial($owner->fresh()))
        ->toThrow(HttpException::class);
});
