<?php

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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
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

afterEach(function () {
    $this->travelBack();
});

function fixtureAlert(User $user, array $data = [])
{
    return $user->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(),
        'type' => \App\Notifications\BookingCreatedNotification::class,
        'data' => $data + ['title' => 'Fixture update', 'message' => 'Your appointment was updated.']]);
}

it('provides an owner inbox with scoped read and delete operations', function () {
    $own = fixtureAlert($this->owner);
    $foreign = fixtureAlert($this->customer, ['title' => 'Private customer alert']);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    $this->get('/filament/notification-inbox')->assertOk()->assertSee('Fixture update')->assertDontSee('Private customer alert')
        ->assertHeader('Cache-Control');
    expect($this->get('/filament/notification-inbox')->headers->get('Cache-Control'))->toContain('no-store');
    $component = Livewire::test(\App\Filament\Admin\Pages\NotificationInbox::class);
    $component->call('readNotification', $own->id);
    expect($own->fresh()->read_at)->not->toBeNull()->and($foreign->fresh()->read_at)->toBeNull();
    $component->call('deleteNotification', $own->id)->assertSee('No notifications yet.');
    expect($foreign->fresh())->not->toBeNull();
});

it('denies forged notification IDs and stale approval on component mutations', function () {
    $own = fixtureAlert($this->owner);
    $foreign = fixtureAlert($this->customer);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    expect(fn () => Livewire::test(\App\Filament\Admin\Pages\NotificationInbox::class)->call('deleteNotification', $foreign->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    $component = Livewire::test(\App\Filament\Admin\Pages\NotificationInbox::class);
    $this->owner->update(['is_approved' => false]);
    $component->call('readNotification', $own->id)->assertForbidden();
    expect($own->fresh()->read_at)->toBeNull();
});

it('polls scoped unread counts while allowing expired owners to recover their account', function () {
    $this->paid->update(['ends_at' => now()->subDay()]);
    $this->actingAs($this->owner);
    $component = Livewire::test(\App\Livewire\NotificationBell::class)->assertSee('Notifications: 0 unread');
    fixtureAlert($this->customer);
    fixtureAlert($this->owner);
    $component->call('$refresh')->assertSee('Notifications: 1 unread');
    $this->get('/filament/notification-inbox')->assertOk();
    $this->owner->update(['email_verified_at' => null]);
    $component->call('$refresh')->assertForbidden();
});

it('escapes notification content and ignores stored external destinations', function () {
    fixtureAlert($this->owner, ['title' => '<img src=x onerror=alert(1)>', 'message' => '<script>bad()</script>',
        'link' => 'javascript:alert(1)']);
    $this->actingAs($this->owner);
    $this->get('/filament/notification-inbox')->assertOk()->assertDontSee('<script>bad()', false)
        ->assertDontSee('javascript:alert')->assertSee('&lt;script&gt;bad()', false);
});

it('shares private verified notification API controls without crossing account boundaries', function () {
    $own = fixtureAlert($this->customer, ['link' => 'javascript:alert(1)']);
    $foreign = fixtureAlert($this->owner);
    $this->actingAs($this->customer);
    $this->getJson('/api/notifications')->assertOk()->assertJsonPath('unread_count', 1)
        ->assertJsonPath('notifications.0.data.link', parse_url(route('customer.history'), PHP_URL_PATH))
        ->assertHeader('Cache-Control');
    $this->patchJson('/api/notifications/'.$foreign->id.'/read')->assertNotFound();
    $this->patchJson('/api/notifications/'.$own->id.'/read')->assertOk()->assertJsonPath('unread_count', 0);
    $this->customer->update(['email_verified_at' => null]);
    $this->getJson('/api/notifications')->assertForbidden();
});

it('generates correct service titles and role appropriate notification destinations', function () {
    $booking = Booking::create(array_replace($this->data, ['user_id' => $this->customer->id, 'status' => 'completed']));
    $this->service->delete();
    $created = (new \App\Notifications\BookingCreatedNotification($booking))->toArray($this->owner);
    $status = new \App\Notifications\BookingStatusChangedNotification($booking);
    expect($created['service'])->toBe('Cut')->and($status->toArray($this->owner)['link'])->toBe('/filament/bookings')
        ->and($status->toArray($this->customer)['link'])->toBe('/dashboard/bookings');
    $expiry = (new \App\Notifications\SubscriptionExpiringNotification($this->paid))->toArray($this->owner);
    expect($expiry['link'])->toBe('/owner/subscription');
});

it('normalizes WhatsApp phone numbers and encodes complete plain text messages safely', function () {
    $booking = Booking::create(array_replace($this->data, ['name' => '<script>name & guest</script>', 'status' => 'completed']));
    $service = app(\App\Services\BookingWhatsApp::class);
    expect($service->phone('077 123 4567'))->toBe('94771234567')->and($service->phone('+44 7700 900123'))->toBe('447700900123')
        ->and($service->phone('0044 7700 900123'))->toBe('447700900123')->and($service->phone('0771234567<script>'))->toBeNull();
    $this->service->delete();
    $url = $service->url($this->owner, $booking);
    expect($url)->toStartWith('https://wa.me/94700000000?text=')->not->toContain('<script>');
    parse_str(parse_url($url, PHP_URL_QUERY), $query);
    expect($query['text'])->toContain('Cut is completed')->toContain('<script>name & guest</script>');
});

it('denies WhatsApp access to foreign bookings and revoked owner access', function () {
    $otherSalon = $this->salon->replicate();
    $otherSalon->fill(['created_by' => $this->admin->id])->save();
    $foreign = Booking::create(array_replace($this->data, ['salon_id' => $otherSalon->id]));
    $service = app(\App\Services\BookingWhatsApp::class);
    expect(fn () => $service->url($this->owner, $foreign))->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
    $this->owner->update(['is_approved' => false]);
    expect(fn () => $service->url($this->owner, $foreign))->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('keeps appointment wall times identical for different browser offsets including midnight boundaries', function () {
    $this->package->update(['calendar' => true]);
    $booking = Booking::create(array_replace($this->data, ['time' => '00:30', 'status' => 'completed']));
    $this->service->delete();
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    $widget = app(\App\Filament\Admin\Widgets\BookingCalendarWidget::class);
    foreach ([-480, 0, 330, 720] as $offset) {
        $events = $widget->getEventsJs(['startStr' => today()->toDateString(),
            'endStr' => today()->addDays(7)->toDateString(), 'tzOffset' => $offset]);
        expect($events[0]['start'])->toBe($booking->date->format('Y-m-d').'T00:30:00')
            ->and($events[0]['end'])->toBe($booking->date->format('Y-m-d').'T01:00:00')
            ->and($events[0]['editable'])->toBeFalse();
    }
});

it('makes calendar mutations unavailable even when a client forges action names', function () {
    $this->package->update(['calendar' => true]);
    $booking = Booking::create($this->data);
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    Livewire::test(\App\Filament\Admin\Widgets\BookingCalendarWidget::class)->call('onEventClickJs',
        ['event' => ['extendedProps' => ['model' => Booking::class, 'key' => $booking->id]]], 'delete')->assertForbidden();
    expect(Booking::find($booking->id))->not->toBeNull();
});

it('closes an already opened notification stream after approval or token revocation', function () {
    $response = $this->actingAs($this->owner)->get('/api/notifications/stream')->assertOk();
    $this->owner->update(['is_approved' => false]);
    expect($response->streamedContent())->toBe('');
    auth()->guard('web')->logout();
    $this->app['auth']->forgetGuards();
    $token = $this->customer->createToken('stream-fixture');
    $response = $this->withToken($token->plainTextToken)->get('/api/notifications/stream')->assertOk();
    $token->accessToken->delete();
    expect($response->streamedContent())->toBe('');
});
