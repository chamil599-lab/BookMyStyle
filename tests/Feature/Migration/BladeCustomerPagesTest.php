<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\FavoriteSalon;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;
use App\Services\CustomerDashboard;
use App\Services\FavoriteService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Livewire\Volt\Volt;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Notification::fake();
    $this->travelTo(now()->startOfDay()->addHours(12));
    foreach (['owner', 'customer', 'admin'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->other = User::factory()->create();
    $this->other->assignRole('customer');
    $this->salon = Salon::create(['name' => 'Amber Studio', 'owner_name' => 'Owner', 'location' => 'Kandy',
        'created_by' => $this->owner->id, 'updated_by' => $this->owner->id, 'show_price' => true]);
    $department = Department::create(['name' => 'Hair', 'slug' => 'hair', 'is_active' => true]);
    $category = Category::create(['name' => 'Cuts', 'department_id' => $department->id]);
    $this->service = Service::create(['title' => 'Signature cut', 'slug' => 'signature-cut', 'description' => 'A fresh cut.',
        'created_by' => $this->owner->id, 'updated_by' => $this->owner->id, 'department_id' => $department->id,
        'category_id' => $category->id, 'price' => 9999.55, 'duration' => 60, 'status' => 'published']);
    $this->makeBooking = fn (array $overrides = []) => Booking::create(array_merge([
        'user_id' => $this->customer->id, 'salon_id' => $this->salon->id, 'service_id' => $this->service->id,
        'name' => 'Customer', 'email' => $this->customer->email, 'phone' => '0700000000',
        'date' => today()->addDay()->format('Y-m-d'), 'time' => '10:00', 'status' => 'pending',
    ], $overrides));
});

afterEach(function () {
    $this->travelBack();
});

it('serves private native customer pages without the React bundle', function ($path, $title) {
    $response = $this->actingAs($this->customer)->get($path)->assertOk()->assertSee($title)
        ->assertSee('Account navigation')->assertHeader('X-Robots-Tag', 'noindex, nofollow')
        ->assertDontSee('id="root"', false);
    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private');
})->with([
    ['/dashboard', 'Your next appointments'], ['/dashboard/upcoming', 'Upcoming bookings'],
    ['/dashboard/history', 'Booking history'], ['/dashboard/favorites', 'Favorite salons'],
    ['/dashboard/notifications', 'Notifications'],
]);

it('requires login verification and the customer role on customer pages', function () {
    foreach (['upcoming', 'history', 'favorites', 'notifications'] as $page) {
        $this->get('/dashboard/'.$page)->assertRedirect(route('login'));
    }
    $this->customer->update(['email_verified_at' => null]);
    $this->actingAs($this->customer)->get('/dashboard/upcoming')->assertRedirect(route('verification.notice'));
    $this->actingAs($this->owner)->get('/dashboard/history')->assertForbidden();
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->actingAs($admin)->get('/dashboard/favorites')->assertForbidden();
});

it('keeps profile and password bookmarks native and accessible after an email change', function () {
    $this->customer->update(['email_verified_at' => null]);
    $this->actingAs($this->customer)->get('/dashboard/profile')->assertRedirect('/profile');
    $this->get('/profile')->assertOk()->assertSee('Profile Information');
    $this->get('/dashboard/change-password')->assertRedirect('/settings/password');
    $this->get('/settings/password')->assertOk()->assertSee('Update password');
});

it('partitions bookings accurately including appointments earlier today and future terminal statuses', function () {
    $future = ($this->makeBooking)();
    $laterToday = ($this->makeBooking)(['date' => today(), 'time' => '14:00', 'status' => 'approved']);
    $earlierToday = ($this->makeBooking)(['date' => today(), 'time' => '10:00', 'status' => 'approved']);
    $past = ($this->makeBooking)(['date' => today()->subDay(), 'status' => 'approved']);
    $completed = ($this->makeBooking)(['status' => 'completed']);
    $rejected = ($this->makeBooking)(['status' => 'rejected']);
    $dashboard = app(CustomerDashboard::class);
    expect($dashboard->upcoming($this->customer)->pluck('id')->all())->toBe([$laterToday->id, $future->id]);
    expect($dashboard->history($this->customer)->pluck('id')->sort()->values()->all())->toBe([$earlierToday->id, $past->id, $completed->id, $rejected->id]);
    expect($dashboard->stats($this->customer))->toMatchArray(['total_bookings' => 6, 'upcoming_bookings' => 2,
        'completed_bookings' => 1, 'cancelled_bookings' => 1]);
    Sanctum::actingAs($this->customer);
    $this->getJson('/api/customer/stats')->assertJsonPath('data.completed_bookings', 1)->assertJsonPath('data.upcoming_bookings', 2);
    $this->getJson('/api/customer/bookings/history')->assertJsonPath('meta.total', 4);
});

it('scopes lists and cancellation to account bookings and verified unclaimed guest bookings', function () {
    $mine = ($this->makeBooking)();
    $guest = ($this->makeBooking)(['user_id' => null]);
    $foreign = ($this->makeBooking)(['user_id' => $this->other->id]); // Same email cannot claim another account's booking.
    $this->actingAs($this->customer)->get('/dashboard/upcoming')->assertSee('data-booking-id="'.$mine->id.'"', false)
        ->assertSee('data-booking-id="'.$guest->id.'"', false)->assertDontSee('data-booking-id="'.$foreign->id.'"', false);
    $this->post('/dashboard/bookings/'.$foreign->id.'/cancel')->assertNotFound();
    expect($foreign->fresh()->status)->toBe('pending');
    $this->post('/dashboard/bookings/'.$guest->id.'/cancel')->assertRedirect(route('customer.upcoming'));
    expect($guest->fresh()->status)->toBe('rejected');
    $this->customer->update(['email_verified_at' => null]);
    expect(app(CustomerDashboard::class)->bookings($this->customer)->pluck('id')->all())->toBe([$mine->id]);
});

it('denies cross-account API booking references even when submitted identity matches the victim', function () {
    $foreign = ($this->makeBooking)(['user_id' => $this->other->id]);
    Sanctum::actingAs($this->customer);
    $this->postJson('/api/bookings/my-bookings', ['user_id' => $this->other->id, 'email' => $this->other->email])
        ->assertOk()->assertExactJson([]);
    $this->patchJson('/api/customer/bookings/'.$foreign->id.'/cancel', ['user_id' => $this->other->id])->assertNotFound();
    expect($foreign->fresh()->status)->toBe('pending');
});

it('denies foreign notification UUIDs across browser and API mutations', function () {
    $foreign = $this->other->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'idor-fixture',
        'data' => ['message' => 'Private foreign notification']]);
    $this->actingAs($this->customer);
    $this->post('/dashboard/notifications/'.$foreign->id.'/read')->assertNotFound();
    $this->delete('/dashboard/notifications/'.$foreign->id)->assertNotFound();
    Sanctum::actingAs($this->customer);
    $this->getJson('/api/notifications')->assertOk()->assertJsonCount(0, 'notifications');
    $this->patchJson('/api/notifications/'.$foreign->id.'/read')->assertNotFound();
    $this->deleteJson('/api/notifications/'.$foreign->id)->assertNotFound();
    $this->postJson('/api/notifications/read-all')->assertOk();
    $this->deleteJson('/api/notifications')->assertOk();
    expect($foreign->fresh())->not->toBeNull()->and($foreign->fresh()->read_at)->toBeNull();
});

it('rejects repeated and completed cancellation without changing booking state', function () {
    $booking = ($this->makeBooking)();
    $this->actingAs($this->customer)->post('/dashboard/bookings/'.$booking->id.'/cancel')->assertRedirect();
    $this->post('/dashboard/bookings/'.$booking->id.'/cancel')->assertSessionHasErrors('status');
    $completed = ($this->makeBooking)(['status' => 'completed']);
    $this->post('/dashboard/bookings/'.$completed->id.'/cancel')->assertSessionHasErrors('status');
    expect($completed->fresh()->status)->toBe('completed');
    $this->get('/dashboard/upcoming')->assertDontSee('data-booking-id="'.$booking->id.'"', false);
    $this->get('/dashboard/history')->assertSee('data-booking-id="'.$booking->id.'"', false);
});

it('preserves deleted service titles for history while suppressing hidden prices in HTML and JSON', function () {
    ($this->makeBooking)(['date' => today()->subDay()]);
    $this->service->delete();
    $this->salon->update(['show_price' => false]);
    $this->actingAs($this->customer)->get('/dashboard/history')->assertSee('Signature cut')->assertSee('Contact salon for pricing')
        ->assertDontSee('9,999.55')->assertDontSee('9999.55');
    Sanctum::actingAs($this->customer);
    $this->getJson('/api/customer/bookings/history')->assertJsonPath('data.0.service.name', 'Signature cut')
        ->assertJsonPath('data.0.service.price', null);
});

it('paginates native lists and rejects malformed page parameters', function () {
    for ($i = 0; $i < 12; $i++) {
        ($this->makeBooking)();
    }
    $this->actingAs($this->customer)->get('/dashboard/upcoming')->assertOk()->assertSee('page=2', false);
    $this->get('/dashboard/upcoming?page=2')->assertOk()->assertSee('data-booking-id="12"', false);
    $this->getJson('/dashboard/history?page[]=bad')->assertUnprocessable();
    $this->getJson('/dashboard/favorites?page=-1')->assertUnprocessable();
});

it('saves and removes card favorites idempotently for the current verified user', function () {
    app(FavoriteService::class)->add($this->other, $this->salon->id);
    $url = '/salons/'.$this->salon->id.'/favorite';
    $this->actingAs($this->customer)->postJson($url)->assertOk()->assertJsonPath('favorited', true);
    $this->postJson($url)->assertOk()->assertJsonPath('favorited', true);
    expect(FavoriteSalon::where('user_id', $this->customer->id)->count())->toBe(1);
    expect((bool) app(\App\Services\PublicCatalogue::class)->cardSalons()->findOrFail($this->salon->id)->is_favorited)->toBeTrue();
    $this->deleteJson($url)->assertOk()->assertJsonPath('favorited', false);
    $this->deleteJson($url)->assertOk()->assertJsonPath('favorited', false);
    expect(FavoriteSalon::where('user_id', $this->customer->id)->count())->toBe(0);
    expect(FavoriteSalon::where('user_id', $this->other->id)->count())->toBe(1);
});

it('protects card favorites from guests unverified users and hidden salons', function () {
    $url = '/salons/'.$this->salon->id.'/favorite';
    $this->postJson($url)->assertUnauthorized();
    $this->deleteJson($url)->assertUnauthorized();
    $this->customer->update(['email_verified_at' => null]);
    $this->actingAs($this->customer)->postJson($url)->assertForbidden();
    $this->customer->update(['email_verified_at' => now()]);
    $this->owner->update(['is_approved' => false]);
    $this->postJson($url)->assertNotFound();
    $this->assertDatabaseCount('favorite_salons', 0);
});

it('keeps favorite changes scoped and hides salons whose owners lose approval', function () {
    $favorites = app(FavoriteService::class);
    expect($favorites->add($this->customer, $this->salon->id))->toBeTrue();
    expect($favorites->add($this->customer, $this->salon->id))->toBeFalse();
    $favorites->add($this->other, $this->salon->id);
    $this->actingAs($this->customer)->get('/dashboard/favorites')->assertSee('Amber Studio');
    $this->delete('/dashboard/favorites/'.$this->salon->id)->assertRedirect(route('customer.favorites'));
    expect(FavoriteSalon::where('user_id', $this->other->id)->count())->toBe(1);
    $favorites->add($this->customer, $this->salon->id);
    $this->owner->update(['is_approved' => false]);
    $this->get('/dashboard/favorites')->assertDontSee('Amber Studio');
    Sanctum::actingAs($this->customer);
    $this->getJson('/api/customer/favorites')->assertJsonPath('data', []);
    $this->postJson('/api/customer/favorites/'.$this->salon->id.'/toggle')->assertNotFound();
    expect($favorites->remove($this->customer, $this->salon->id))->toBeTrue();
});

it('escapes customer notification content and never trusts stored notification links', function () {
    $id = (string) Str::uuid();
    $foreignId = (string) Str::uuid();
    foreach ([$id => $this->customer, $foreignId => $this->other] as $notificationId => $user) {
        $user->notifications()->create(['id' => $notificationId, 'type' => 'fixture',
            'data' => ['title' => '<script>bad()</script>', 'message' => 'Private booking update', 'link' => 'javascript:bad()']]);
    }
    $this->actingAs($this->customer)->get('/dashboard/notifications')->assertSee('&lt;script&gt;', false)
        ->assertDontSee('<script>bad()', false)->assertDontSee('javascript:bad()', false);
    $this->post('/dashboard/notifications/'.$foreignId.'/read')->assertNotFound();
    $this->delete('/dashboard/notifications/'.$foreignId)->assertNotFound();
    $this->post('/dashboard/notifications/read-all')->assertRedirect();
    expect($this->customer->unreadNotifications()->count())->toBe(0)->and($this->other->unreadNotifications()->count())->toBe(1);
    $this->delete('/dashboard/notifications/'.$id)->assertRedirect();
    $this->customer->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'fixture', 'data' => ['title' => 'Update']]);
    $this->delete('/dashboard/notifications')->assertRedirect();
    expect($this->customer->notifications()->count())->toBe(0)->and($this->other->notifications()->count())->toBe(1);
    $this->get('/dashboard/bookings')->assertRedirect('/dashboard/upcoming');
});

it('keeps native profile identity protected and gates guest-booking claims after email changes', function () {
    $guest = ($this->makeBooking)(['user_id' => null]);
    $this->actingAs($this->customer);
    Volt::test('profile.update-profile-information-form')->set('name', 'New name')->set('email', 'new@example.test')
        ->call('updateProfileInformation')->assertHasNoErrors();
    expect($this->customer->fresh()->email_verified_at)->toBeNull();
    $this->get('/dashboard/history')->assertRedirect(route('verification.notice'));
    expect(app(CustomerDashboard::class)->bookings($this->customer->fresh())->whereKey($guest->id)->exists())->toBeFalse();
});

it('requires the current password and revokes tokens and other database sessions when changing it', function () {
    config(['session.driver' => 'database']);
    $this->customer->createToken('old-browser');
    DB::table('sessions')->insert(['id' => 'other-device', 'user_id' => $this->customer->id,
        'payload' => base64_encode(serialize([])), 'last_activity' => now()->timestamp]);
    $oldRemember = $this->customer->remember_token;
    $this->actingAs($this->customer);
    Volt::test('settings.password')->set('current_password', 'wrong')->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')->call('updatePassword')->assertHasErrors('current_password');
    expect($this->customer->tokens()->count())->toBe(1);
    Volt::test('settings.password')->set('current_password', 'password')->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')->call('updatePassword')->assertHasNoErrors();
    expect(Hash::check('new-password', $this->customer->fresh()->password))->toBeTrue()
        ->and($this->customer->tokens()->count())->toBe(0)->and($this->customer->fresh()->remember_token)->not->toBe($oldRemember)
        ->and(DB::table('sessions')->where('id', 'other-device')->exists())->toBeFalse();
});

it('rejects malformed API identifiers and unverified customer mutations', function () {
    Sanctum::actingAs($this->customer);
    $this->patchJson('/api/customer/bookings/not-an-id/cancel')->assertNotFound();
    $this->getJson('/api/customer/favorites/not-an-id/check')->assertNotFound();
    $this->customer->update(['email_verified_at' => null]);
    $booking = ($this->makeBooking)();
    Sanctum::actingAs($this->customer->fresh());
    $this->patchJson('/api/customer/bookings/'.$booking->id.'/cancel')->assertForbidden();
    $this->postJson('/api/customer/favorites/'.$this->salon->id.'/toggle')->assertForbidden();
    expect($booking->fresh()->status)->toBe('pending')->and(FavoriteSalon::count())->toBe(0);
});
