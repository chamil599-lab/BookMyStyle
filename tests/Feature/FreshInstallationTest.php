<?php

use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\FreshInstallationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

it('initializes an approved verified administrator and plans without demo records', function () {
    $admin = app(FreshInstallationService::class)->initialize([
        'name' => 'Site administrator', 'email' => 'administrator@example.test', 'password' => 'test-only-unique-password',
    ]);
    expect(User::count())->toBe(1)
        ->and($admin->hasRole('admin'))->toBeTrue()
        ->and($admin->hasVerifiedEmail())->toBeTrue()
        ->and($admin->is_approved)->toBeTrue()
        ->and(Hash::check('test-only-unique-password', $admin->password))->toBeTrue()
        ->and(Role::where('guard_name', 'web')->pluck('name')->all())->toContain('admin', 'owner', 'customer', 'user')
        ->and(SubscriptionPackage::count())->toBe(3)
        ->and(SubscriptionPackage::where('slug', 'pro-salon')->first()->isAvailable())->toBeTrue();
    $this->assertDatabaseCount('salons', 0);
    $this->assertDatabaseCount('user_subscriptions', 0);
});

it('refuses to initialize over existing accounts', function () {
    User::factory()->create();
    expect(fn () => app(FreshInstallationService::class)->initialize([
        'name' => 'Site administrator', 'email' => 'administrator@example.test', 'password' => 'test-only-unique-password',
    ]))->toThrow(RuntimeException::class);
    expect(User::count())->toBe(1)->and(SubscriptionPackage::count())->toBe(0);
});

it('rejects missing or short administrator credentials before creating data', function () {
    expect(fn () => app(FreshInstallationService::class)->initialize([
        'name' => '', 'email' => '', 'password' => 'short',
    ]))->toThrow(ValidationException::class);
    expect(User::count())->toBe(0)->and(SubscriptionPackage::count())->toBe(0);
});
