<?php

use App\Livewire\BookingForm;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\BookingService;
use App\Services\SubscriptionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->travelTo(now()->startOfDay()->addHours(8));
    Mail::fake();
    Notification::fake();
    foreach (['admin', 'owner', 'customer'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->package = SubscriptionPackage::create(['name' => 'Basic', 'slug' => 'basic', 'price' => 100,
        'duration_days' => 30, 'max_salons' => 5, 'max_staff' => 5, 'max_services' => 5, 'is_active' => true]);
    $this->paid = UserSubscription::create(['user_id' => $this->owner->id, 'package_id' => $this->package->id,
        'amount_paid' => 100, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDays(10)]);
    $department = Department::create(['name' => 'Hair', 'slug' => 'hair']);
    $category = Category::create(['name' => 'Cut', 'department_id' => $department->id]);
    $this->salon = Salon::create(['name' => 'Salon', 'owner_name' => 'Owner', 'created_by' => $this->owner->id,
        'updated_by' => $this->owner->id, 'open_time' => '09:00', 'close_time' => '18:00', 'number_of_staff' => 1]);
    $this->service = Service::create(['title' => 'Cut', 'slug' => 'cut', 'description' => 'Cut',
        'department_id' => $department->id, 'category_id' => $category->id, 'price' => 100,
        'duration' => 30, 'status' => 'published', 'created_by' => $this->owner->id, 'updated_by' => $this->owner->id]);
    $this->data = ['salon_id' => $this->salon->id, 'service_id' => $this->service->id,
        'name' => 'Customer', 'email' => 'customer@gmail.com', 'phone' => '0700000000',
        'date' => today()->addDay()->format('Y-m-d'), 'time' => '10:00'];
    $this->bookings = app(BookingService::class);
    $this->subscriptions = app(SubscriptionService::class);
    $this->payment = ['payment_method' => 'bank_transfer', 'transaction_id' => 'TEST-123'];
});

it('forces public status and account ownership from the authenticated actor', function () {
    $booking = $this->bookings->create($this->data + ['status' => 'approved', 'user_id' => $this->admin->id], $this->customer);
    expect($booking->status)->toBe('pending')->and($booking->user_id)->toBe($this->customer->id);
});

it('renders booking decision emails after their service has been soft deleted', function () {
    $booking = Booking::create($this->data + ['status' => 'approved']);
    $this->service->delete();
    foreach (['approved', 'rejected'] as $status) {
        $booking->status = $status;
        $html = (new \App\Mail\BookingStatusChanged($booking))->render();
        expect($html)->toContain('Cut')->toContain($booking->name);
    }
});

it('discards forged booking identity timestamps and state through the public API', function () {
    Sanctum::actingAs($this->customer);
    $this->postJson('/api/bookings', $this->data + ['id' => 999999, 'user_id' => $this->admin->id,
        'status' => 'approved', 'created_at' => '2000-01-01', 'updated_at' => '2000-01-01', 'deleted_at' => now()->toIso8601String()])
        ->assertCreated();
    $booking = Booking::firstOrFail();
    expect($booking->id)->not->toBe(999999)->and($booking->user_id)->toBe($this->customer->id)
        ->and($booking->status)->toBe('pending')->and($booking->created_at->isToday())->toBeTrue();
});

it('discards protected subscription fields in payment updates', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    $before = $pending->only(['user_id', 'package_id', 'status', 'amount_paid', 'is_trial', 'starts_at', 'ends_at', 'features_snapshot']);
    $this->subscriptions->updatePayment($pending, $this->owner, ['payment_method' => 'one_pay', 'transaction_id' => null,
        'user_id' => $this->admin->id, 'package_id' => 999999, 'status' => 'active', 'amount_paid' => 999999,
        'is_trial' => true, 'starts_at' => now(), 'ends_at' => now()->addYears(20), 'features_snapshot' => ['max_salons' => 999999]]);
    expect($pending->fresh()->only(array_keys($before)))->toEqual($before)
        ->and($pending->fresh()->payment_method)->toBe('one_pay');
});

it('discards ownership and primary key fields in catalogue service updates', function () {
    $before = $this->service->only(['id', 'created_by', 'created_at']);
    $data = $this->service->only(['slug', 'description', 'department_id', 'category_id', 'price', 'duration', 'status']);
    app(\App\Services\CatalogueManagement::class)->updateService($this->owner, $this->service, $data + [
        'title' => 'Allowed title change', 'id' => 999999, 'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id, 'created_at' => '2000-01-01', 'deleted_at' => now()]);
    expect($this->service->fresh()->only(array_keys($before)))->toEqual($before)
        ->and($this->service->fresh()->updated_by)->toBe($this->owner->id)
        ->and($this->service->fresh()->title)->toBe('Allowed title change')
        ->and($this->service->fresh()->deleted_at)->toBeNull();
});

it('prevents double booking and treats adjacent appointments as available', function () {
    $this->bookings->create($this->data);
    try {
        $this->bookings->create($this->data);
        $this->fail('A full slot must be rejected.');
    } catch (ValidationException $exception) {
        expect($exception->status)->toBe(409);
    }
    $this->bookings->create(array_replace($this->data, ['time' => '10:30']));
    expect(Booking::count())->toBe(2);
});

it('uses peak concurrent occupancy across long services rather than counting disjoint overlaps', function () {
    $this->salon->update(['number_of_staff' => 2]);
    $this->bookings->create($this->data);
    $this->bookings->create(array_replace($this->data, ['time' => '10:30']));
    $long = $this->service->replicate();
    $long->slug = 'long';
    $long->duration = 60;
    $long->save();
    $this->bookings->create(array_replace($this->data, ['service_id' => $long->id]));
    expect($this->bookings->remainingCapacity($this->salon, $this->data['date'], '10:00', $long->id))->toBe(0);
});

it('enforces opening hours slot alignment service duration and future public appointments', function ($changes) {
    expect(fn () => $this->bookings->create(array_replace($this->data, $changes)))->toThrow(ValidationException::class);
})->with([[['time' => '08:30']], [['time' => '10:15']], [['time' => '18:00']], [['date' => '2000-01-01']]]);

it('does not treat updated_by as ownership of a service', function () {
    $this->service->update(['created_by' => $this->admin->id, 'updated_by' => $this->owner->id]);
    expect(fn () => $this->bookings->create($this->data))->toThrow(ValidationException::class);
});

it('rejects booking reactivation when its former slot has filled', function () {
    $booking = $this->bookings->create($this->data);
    $booking = $this->bookings->changeStatus($booking, 'rejected', $this->owner);
    $this->bookings->create($this->data);
    expect(fn () => $this->bookings->changeStatus($booking, 'approved', $this->owner))->toThrow(ValidationException::class);
});

it('does not reopen completed bookings', function () {
    $booking = $this->bookings->create($this->data);
    $booking = $this->bookings->changeStatus($booking, 'completed', $this->owner);
    expect(fn () => $this->bookings->changeStatus($booking, 'pending', $this->admin))->toThrow(ValidationException::class);
});

it('enforces monthly quotas for public and management writes including a zero limit', function () {
    $this->package->update(['max_bookings_per_month' => 0]);
    expect(fn () => $this->bookings->create($this->data))->toThrow(ValidationException::class)
        ->and(fn () => $this->bookings->create($this->data, $this->owner, true))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});

it('counts only bookings created within the current calendar month', function () {
    $booking = $this->bookings->create($this->data);
    $booking->forceFill(['created_at' => now()->addMonth()])->save();
    expect($this->owner->getMonthlyBookingsCount())->toBe(0);
});

it('lets a customer cancel their booking and frees its capacity', function () {
    $booking = $this->bookings->create($this->data, $this->customer);
    $this->bookings->cancel($booking, $this->customer);
    expect($this->bookings->remainingCapacity($this->salon, $this->data['date'], '10:00', $this->service->id))->toBe(1);
});

it('sends notifications only after commit and never after rollback', function () {
    Notification::fake();
    DB::beginTransaction();
    $this->bookings->create($this->data);
    Notification::assertNothingSent();
    DB::rollBack();
    Notification::assertNothingSent();
    expect(Booking::count())->toBe(0);
    $booking = $this->bookings->create($this->data);
    Notification::assertSentTo($this->owner, \App\Notifications\BookingCreatedNotification::class);
    DB::beginTransaction();
    $this->bookings->changeStatus($booking, 'approved', $this->owner);
    Mail::assertNothingSent();
    DB::rollBack();
    Mail::assertNothingSent();
});

it('uses shared capacity validation for the Livewire form', function () {
    $this->bookings->create($this->data);
    Livewire::test(BookingForm::class, ['salonId' => $this->salon->id])
        ->set('service_id', $this->service->id)->set('name', 'Customer')->set('email', 'customer@gmail.com')
        ->set('phone', '0700000000')->set('date', $this->data['date'])->set('time', '10:00')
        ->call('submit')->assertHasErrors(['time']);
    expect(Booking::count())->toBe(1);
});

it('preserves the active paid plan when a new request is submitted', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    expect($pending->id)->not->toBe($this->paid->id)->and($this->paid->fresh()->isActive())->toBeTrue()
        ->and($this->paid->fresh()->amount_paid)->toBe('100.00');
    expect(fn () => $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner))->toThrow(ValidationException::class);
});

it('rejects duplicate pending requests without extending paid access', function () {
    $end = $this->paid->ends_at->copy();
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    expect(fn () => $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner))
        ->toThrow(ValidationException::class);
    expect($this->owner->subscriptions()->pending()->count())->toBe(1)
        ->and($this->paid->fresh()->ends_at->equalTo($end))->toBeTrue()
        ->and($pending->fresh()->isActive())->toBeFalse();
});

it('cannot approve a cancelled payment request or alter its payment details', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    $this->subscriptions->cancel($pending, $this->admin);
    expect(fn () => $this->subscriptions->approve($pending, $this->admin, 100))->toThrow(ValidationException::class);
    expect(fn () => $this->subscriptions->updatePayment($pending, $this->owner, ['payment_method' => 'one_pay']))
        ->toThrow(ValidationException::class);
    expect($pending->fresh()->status)->toBe('canceled')->and($this->paid->fresh()->isActive())->toBeTrue();
});

it('does not allow stale booking edits to overwrite a newer cancellation', function () {
    $booking = $this->bookings->create($this->data, $this->customer);
    $stale = Booking::findOrFail($booking->id);
    $this->bookings->cancel($booking, $this->customer);
    expect(fn () => $this->bookings->update($stale, ['status' => 'approved'], $this->owner))
        ->toThrow(ValidationException::class);
    expect($booking->fresh()->status)->toBe('rejected');
});

it('rejects unpublished public services without consuming capacity', function () {
    $this->service->update(['status' => 'draft']);
    expect(fn () => $this->bookings->create($this->data, $this->customer))->toThrow(ValidationException::class);
    expect(Booking::count())->toBe(0);
});

it('queues same-plan renewals and excludes future subscriptions from current access', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    $renewal = $this->subscriptions->approve($pending, $this->admin, 100);
    expect($renewal->starts_at->equalTo($this->paid->ends_at))->toBeTrue()
        ->and($renewal->isActive())->toBeFalse()->and($this->owner->activeSubscription()->first()->id)->toBe($this->paid->id);
    $this->travelTo($this->paid->ends_at);
    expect($this->owner->activeSubscription()->first()->id)->toBe($renewal->id);
});

it('starts approved plan changes immediately while preserving the old payment history', function () {
    $package = $this->package->replicate();
    $package->slug = 'premium';
    $package->price = 200;
    $package->save();
    $pending = $this->subscriptions->request($this->owner, $package, $this->payment, $this->owner);
    $changed = $this->subscriptions->approve($pending, $this->admin, 200);
    expect($changed->starts_at->equalTo(now()))->toBeTrue()->and($this->paid->fresh()->status)->toBe('canceled')
        ->and($this->paid->fresh()->amount_paid)->toBe('100.00')->and($this->paid->fresh()->package_id)->toBe($this->package->id);
});

it('rejects pending requests without interrupting paid access', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    $this->subscriptions->cancel($pending, $this->admin);
    expect($this->paid->fresh()->isActive())->toBeTrue()->and($pending->fresh()->status)->toBe('canceled');
});

it('requires admin approval and rejects repeated approval', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    expect(fn () => $this->subscriptions->approve($pending, $this->owner, 100))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
    $this->subscriptions->approve($pending, $this->admin, 100);
    expect(fn () => $this->subscriptions->approve($pending, $this->admin, 100))->toThrow(ValidationException::class);
});

it('uses the submitted package duration snapshot for approval', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    $this->package->update(['duration_days' => 90]);
    $approved = $this->subscriptions->approve($pending, $this->admin, 100);
    expect((int) $approved->starts_at->diffInDays($approved->ends_at))->toBe(30);
});

it('allows access until the actual expiry timestamp including the final partial day', function () {
    $this->paid->update(['ends_at' => now()->addHours(2)]);
    Sanctum::actingAs($this->owner);
    $this->getJson('/api/salons/'.$this->salon->id.'/bookings')->assertOk();
    expect($this->paid->daysRemaining())->toBe(1);
    $this->travelTo($this->paid->ends_at);
    expect($this->owner->hasActiveSubscription())->toBeFalse();
    $this->getJson('/api/salons/'.$this->salon->id.'/bookings')->assertForbidden();
});

it('rejects unavailable packages and invalid payment details', function () {
    expect(fn () => $this->subscriptions->request($this->owner, $this->package, ['payment_method' => 'bank_transfer'], $this->owner))->toThrow(ValidationException::class);
    $this->package->update(['is_active' => false]);
    expect(fn () => $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner))->toThrow(ValidationException::class);
});

it('allows payment edits only while pending', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    $this->subscriptions->updatePayment($pending, $this->owner, ['payment_method' => 'one_pay']);
    $this->subscriptions->approve($pending, $this->admin, 100);
    expect(fn () => $this->subscriptions->updatePayment($pending, $this->owner, $this->payment))->toThrow(ValidationException::class);
});

it('preserves the existing public API success and conflict response fields', function () {
    $this->postJson('/api/bookings', $this->data)->assertCreated()->assertJsonPath('remaining_slots', 0)->assertJsonPath('is_registered', false);
    $this->postJson('/api/bookings', $this->data)->assertConflict()->assertJsonStructure(['error', 'errors', 'available_slots']);
});

it('uses shared rules for native plan requests and Filament administrator approval', function () {
    $this->actingAs($this->owner)->post('/subscription/subscribe/'.$this->package->id, $this->payment)
        ->assertRedirect('/filament/user-subscriptions');
    $pending = $this->owner->subscriptions()->pending()->firstOrFail();
    $this->actingAs($this->admin);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    Livewire::test(\App\Filament\Admin\Resources\UserSubscriptions\Pages\ListUserSubscriptions::class)
        ->callTableAction('approve', $pending, data: ['amount_paid' => 100])->assertHasNoActionErrors();
    expect($pending->fresh()->starts_at->equalTo($this->paid->ends_at))->toBeTrue();
});

it('enforces capacity during a Filament create action', function () {
    $this->bookings->create($this->data);
    $this->actingAs($this->admin);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    Livewire::test(\App\Filament\Admin\Resources\Bookings\Pages\CreateBooking::class)
        ->fillForm($this->data + ['status' => 'pending'])->call('create')->assertHasFormErrors(['time']);
    expect(Booking::count())->toBe(1);
});

it('creates Filament subscriptions as requests and protects history during edit', function () {
    $this->actingAs($this->admin);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    Livewire::test(\App\Filament\Admin\Resources\UserSubscriptions\Pages\CreateUserSubscription::class)
        ->fillForm($this->payment + ['user_id' => $this->owner->id, 'package_id' => $this->package->id])
        ->call('create')->assertHasNoFormErrors();
    $pending = $this->owner->subscriptions()->pending()->firstOrFail();
    Livewire::test(\App\Filament\Admin\Resources\UserSubscriptions\Pages\EditUserSubscription::class, ['record' => $pending->id])
        ->fillForm(['payment_method' => 'bank_transfer', 'transaction_id' => 'EDITED'])
        ->call('save')->assertHasNoFormErrors();
    expect($pending->fresh()->transaction_id)->toBe('EDITED')->and($pending->fresh()->status)->toBe('pending')
        ->and($this->paid->fresh()->isActive())->toBeTrue();
});

it('does not grant repeat trials after an owner has subscription history', function () {
    expect($this->subscriptions->grantTrial($this->owner, $this->package, $this->admin))->toBeNull();
    $owner = User::factory()->create(['is_approved' => true]);
    $owner->assignRole('owner');
    $trial = $this->subscriptions->grantTrial($owner, $this->package, $this->admin);
    expect($trial->is_trial)->toBeTrue()->and($trial->isActive())->toBeTrue();
    $trial->update(['status' => 'expired', 'ends_at' => now()->subDay()]);
    expect($this->subscriptions->grantTrial($owner, $this->package, $this->admin))->toBeNull();
});

it('notifies the customer and salon owner once for an administrator status change', function () {
    $booking = $this->bookings->create($this->data, $this->customer);
    Notification::fake();
    $booking = $this->bookings->changeStatus($booking, 'approved', $this->admin);
    $this->bookings->changeStatus($booking, 'approved', $this->admin);
    Notification::assertSentToTimes($this->customer, \App\Notifications\BookingStatusChangedNotification::class, 1);
    Notification::assertSentToTimes($this->owner, \App\Notifications\BookingStatusChangedNotification::class, 1);
    Mail::assertSent(\App\Mail\BookingStatusChanged::class, 1);
});
