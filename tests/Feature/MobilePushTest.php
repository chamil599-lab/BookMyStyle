<?php

use App\Jobs\SendBookingPush;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\PushDevice;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingCreatedNotification;
use App\Notifications\BookingReminderNotification;
use App\Notifications\BookingStatusChangedNotification;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    config(['push.enabled' => true, 'push.project_id' => 'fixture-project',
        'push.credentials' => base_path('phpunit.xml')]);
    Http::preventStrayRequests();
    foreach (['customer', 'owner'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->user = User::factory()->create();
    $this->user->assignRole('customer');
    $this->session = $this->user->createToken('phone');
    $this->deviceToken = str_repeat('fixture-device-', 4);
});

it('requires authentication for device registration', function () {
    $this->putJson('/api/v1/push/device', ['token' => $this->deviceToken])->assertUnauthorized();
});

it('registers an encrypted session-bound device and removes it on revocation', function () {
    $this->withToken($this->session->plainTextToken)->putJson('/api/v1/push/device', ['token' => $this->deviceToken])->assertOk();
    $device = PushDevice::firstOrFail();
    expect($device->user_id)->toBe($this->user->id)
        ->and($device->personal_access_token_id)->toBe($this->session->accessToken->id)
        ->and($device->token)->toBe($this->deviceToken)
        ->and(DB::table('push_devices')->value('token'))->not->toBe($this->deviceToken);
    $this->session->accessToken->delete();
    expect(PushDevice::count())->toBe(0);
});

it('does not let another account delete a device', function () {
    $device = PushDevice::create(['user_id' => $this->user->id,
        'personal_access_token_id' => $this->session->accessToken->id,
        'token_hash' => hash('sha256', $this->deviceToken), 'token' => $this->deviceToken]);
    $other = User::factory()->create()->createToken('other');
    $this->withToken($other->plainTextToken)->deleteJson('/api/v1/push/device', ['token' => $this->deviceToken])->assertOk();
    expect($device->fresh())->not->toBeNull();
});

function pushBookingFixture(User $customer): Booking
{
    $owner = User::factory()->create();
    $owner->assignRole('owner');
    $salon = Salon::create(['name' => 'Push fixture', 'owner_name' => 'Owner',
        'created_by' => $owner->id, 'updated_by' => $owner->id, 'number_of_staff' => 1]);
    $department = Department::create(['name' => 'Push hair', 'slug' => 'push-hair']);
    $category = Category::create(['name' => 'Push cut', 'department_id' => $department->id]);
    $service = Service::create(['title' => 'Push cut', 'slug' => 'push-cut', 'description' => 'Test service', 'department_id' => $department->id,
        'category_id' => $category->id, 'price' => 100, 'duration' => 30, 'status' => 'published',
        'created_by' => $owner->id, 'updated_by' => $owner->id]);
    $at = now('Asia/Colombo')->addHours(2);

    return Booking::create(['user_id' => $customer->id, 'salon_id' => $salon->id, 'service_id' => $service->id,
        'name' => 'Private customer', 'email' => 'private@example.com', 'phone' => '0700000000',
        'date' => $at->toDateString(), 'time' => $at->format('H:i:s'), 'status' => 'approved']);
}

it('sends generic alerts without customer details and prunes unregistered tokens', function () {
    Notification::fake();
    $booking = pushBookingFixture($this->user);
    $device = PushDevice::create(['user_id' => $this->user->id,
        'personal_access_token_id' => $this->session->accessToken->id,
        'token_hash' => hash('sha256', $this->deviceToken), 'token' => $this->deviceToken]);
    Cache::put('push-oauth-'.hash('sha256', 'fixture-project'), 'fake-oauth', 60);
    Http::fake(['fcm.googleapis.com/*' => Http::response(['error' => ['details' => [['errorCode' => 'UNREGISTERED']]]], 404)]);
    (new SendBookingPush($device->id, $booking->id, $this->user->id, $this->session->accessToken->id))->handle();
    Http::assertSent(fn ($r) => $r['message']['data']['user_id'] === (string) $this->user->id
        && ! str_contains(json_encode($r->data()), 'Private customer') && ! str_contains(json_encode($r->data()), 'private@example.com'));
    expect($device->fresh())->toBeNull();
});

it('drops queued alerts after device ownership or session changes', function () {
    $device = PushDevice::create(['user_id' => $this->user->id,
        'personal_access_token_id' => $this->session->accessToken->id,
        'token_hash' => hash('sha256', $this->deviceToken), 'token' => $this->deviceToken]);
    Http::fake();
    (new SendBookingPush($device->id, 1, $this->user->id + 1, $this->session->accessToken->id))->handle();
    $this->session->accessToken->update(['expires_at' => now()->subMinute()]);
    (new SendBookingPush($device->id, 1, $this->user->id, $this->session->accessToken->id))->handle();
    Http::assertNothingSent();
});

it('records each upcoming appointment reminder once', function () {
    Notification::fake();
    $booking = pushBookingFixture($this->user);
    config(['push.reminders_enabled' => true]);
    $this->artisan('bookings:remind')->assertSuccessful();
    $this->artisan('bookings:remind')->assertSuccessful();
    expect(DB::table('booking_reminder_deliveries')->where('booking_id', $booking->id)->count())->toBe(1);
    Notification::assertSentToTimes($this->user, BookingReminderNotification::class, 1);
});

it('queues each booking notification after commit without changing inbox delivery', function () {
    Notification::fake();
    Queue::fake();
    $booking = pushBookingFixture($this->user);
    PushDevice::create(['user_id' => $this->user->id,
        'personal_access_token_id' => $this->session->accessToken->id,
        'token_hash' => hash('sha256', $this->deviceToken), 'token' => $this->deviceToken]);
    foreach ([BookingCreatedNotification::class,
        BookingStatusChangedNotification::class,
        BookingCancelledNotification::class,
        BookingReminderNotification::class] as $type) {
        event(new NotificationSent($this->user, new $type($booking), 'database'));
    }
    Queue::assertPushed(SendBookingPush::class, 4);
    Queue::assertPushed(SendBookingPush::class,
        fn ($job) => $job->afterCommit === true && $job->userId === $this->user->id);
});
