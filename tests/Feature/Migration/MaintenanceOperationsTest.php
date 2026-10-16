<?php

use App\Models\FeaturedSalon;
use App\Models\Salon;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use App\Notifications\SubscriptionExpiringNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schedule;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-10-14 09:00:00', 'Asia/Colombo'));
    Notification::fake();
    config(['operations.featured_cleanup_enabled' => false, 'operations.subscription_reminders_enabled' => false]);
    Role::findOrCreate('owner', 'web');
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
    $this->package = SubscriptionPackage::create(['name' => 'Operations fixture', 'slug' => 'operations-fixture', 'price' => 100,
        'duration_days' => 30, 'max_salons' => 1, 'max_staff' => 1, 'max_services' => 1]);
    $this->subscription = UserSubscription::create(['user_id' => $this->owner->id, 'package_id' => $this->package->id, 'amount_paid' => 100,
        'status' => 'active', 'starts_at' => now()->subMonth(), 'ends_at' => now()->addDays(3)]);
});

it('defaults to reminder dry-run and requires host opt-in for actual delivery', function () {
    $this->artisan('subscriptions:notify-expiring')->expectsOutput('Dry run: 1 eligible reminder(s); no notifications sent.')->assertSuccessful();
    Notification::assertNothingSent();
    $this->artisan('subscriptions:notify-expiring --send')->assertFailed();
    Notification::assertNothingSent();
});

it('avoids duplicate successful reminders and allows a changed expiry to be reminded', function () {
    config(['operations.subscription_reminders_enabled' => true]);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    Notification::assertSentToTimes($this->owner, SubscriptionExpiringNotification::class, 1);
    $this->subscription->update(['ends_at' => now()->addDays(3)->addHour()]);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    Notification::assertSentToTimes($this->owner, SubscriptionExpiringNotification::class, 2);
});

it('suppresses reminders when an approved renewal already covers expiry', function () {
    config(['operations.subscription_reminders_enabled' => true]);
    UserSubscription::create(['user_id' => $this->owner->id, 'package_id' => $this->package->id, 'amount_paid' => 100,
        'status' => 'active', 'starts_at' => $this->subscription->ends_at, 'ends_at' => now()->addDays(33)]);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    Notification::assertNothingSent();
});

it('allows reminder retry after delivery fails instead of marking it successfully sent', function () {
    config(['operations.subscription_reminders_enabled' => true]);
    Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Synthetic notification failure'));
    expect(fn () => Artisan::call('subscriptions:notify-expiring', ['--send' => true]))
        ->toThrow(RuntimeException::class);
    Notification::fake();
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    Notification::assertSentToTimes($this->owner, SubscriptionExpiringNotification::class, 1);
});

it('registers maintenance schedules only after host opt-in', function () {
    $original = Schedule::getFacadeRoot();
    $schedule = new Illuminate\Console\Scheduling\Schedule('Asia/Colombo');
    Schedule::swap($schedule);
    try {
        require base_path('routes/console.php');
        expect($schedule->events())->toBe([]);
        config(['operations.featured_cleanup_enabled' => true, 'operations.subscription_reminders_enabled' => true]);
        require base_path('routes/console.php');
        $events = $schedule->events();
        expect($events)->toHaveCount(2)
            ->and($events[0]->command)->toContain('featured:purge-expired --apply')
            ->and($events[0]->expression)->toBe('0 * * * *')
            ->and($events[1]->command)->toContain('subscriptions:notify-expiring --send')
            ->and($events[1]->expression)->toBe('0 9 * * *')
            ->and($events[1]->timezone)->toBe('Asia/Colombo')
            ->and($events[0]->withoutOverlapping)->toBeTrue()
            ->and($events[1]->withoutOverlapping)->toBeTrue();
    } finally {
        Schedule::swap($original);
    }
});

it('excludes unverified or unapproved owners and future-start subscriptions', function () {
    config(['operations.subscription_reminders_enabled' => true]);
    $this->owner->update(['email_verified_at' => null]);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    $this->owner->update(['email_verified_at' => now(), 'is_approved' => false]);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    $this->owner->update(['is_approved' => true]);
    $this->subscription->update(['starts_at' => now()->addDay()]);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    Notification::assertNothingSent();
});

it('excludes pending subscriptions and uses calendar-date boundaries for reminders', function () {
    config(['operations.subscription_reminders_enabled' => true]);
    $this->subscription->update(['status' => 'pending']);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    $this->subscription->update(['status' => 'active', 'ends_at' => now()->addDays(3)->startOfDay()->subSecond()]);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    $this->subscription->update(['ends_at' => now()->addDays(4)->startOfDay()]);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    Notification::assertNothingSent();
    $this->subscription->update(['ends_at' => now()->addDays(3)->startOfDay()]);
    $this->artisan('subscriptions:notify-expiring --send')->assertSuccessful();
    Notification::assertSentToTimes($this->owner, SubscriptionExpiringNotification::class, 1);
});

it('uses exact featured timestamps and only applies expiration after opt-in', function () {
    $salon = Salon::create(['name' => 'Fixture salon', 'owner_name' => 'Owner', 'created_by' => $this->owner->id, 'updated_by' => $this->owner->id]);
    $base = ['salon_id' => $salon->id, 'active' => true, 'created_by' => $this->owner->id, 'updated_by' => $this->owner->id];
    $expired = FeaturedSalon::create($base + ['ends_at' => now()]);
    $live = FeaturedSalon::create($base + ['ends_at' => now()->addSecond()]);
    $future = FeaturedSalon::create($base + ['starts_at' => now()->addHour(), 'ends_at' => now()->addDay()]);
    expect(FeaturedSalon::active()->pluck('id')->all())->toBe([$live->id]);
    $this->artisan('featured:purge-expired')->assertSuccessful();
    expect($expired->fresh()->active)->toBeTrue();
    $this->artisan('featured:purge-expired --apply')->assertFailed();
    expect($expired->fresh()->active)->toBeTrue();
    config(['operations.featured_cleanup_enabled' => true]);
    $this->artisan('featured:purge-expired --apply')->assertSuccessful();
    expect($expired->fresh()->active)->toBeFalse()->and($live->fresh()->active)->toBeTrue()->and($future->fresh()->active)->toBeTrue();
});
