<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    foreach (['owner', 'customer', 'admin'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
    $this->other = User::factory()->create(['is_approved' => true]);
    $this->other->assignRole('owner');
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->package = SubscriptionPackage::create(['name' => 'Mobile test', 'slug' => 'mobile-test', 'price' => 100,
        'duration_days' => 30, 'max_salons' => 2, 'max_staff' => 3, 'max_services' => 3, 'max_bookings_per_month' => 10, 'analytics' => true, 'calendar' => true, 'is_active' => true]);
    $this->subscription = UserSubscription::create(['user_id' => $this->owner->id, 'package_id' => $this->package->id,
        'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'amount_paid' => 100]);
    $this->salon = Salon::create(['name' => 'Mine', 'owner_name' => 'Owner', 'created_by' => $this->owner->id, 'updated_by' => $this->owner->id,
        'number_of_staff' => 2, 'open_time' => '09:00', 'close_time' => '18:00', 'show_price' => true]);
    $this->foreign = Salon::create(['name' => 'Other', 'owner_name' => 'Other', 'created_by' => $this->other->id, 'updated_by' => $this->other->id, 'number_of_staff' => 1]);
    $d = Department::create(['name' => 'Hair', 'slug' => 'hair']);
    $c = Category::create(['name' => 'Cut', 'slug' => 'cut', 'department_id' => $d->id]);
    $this->serviceData = ['title' => 'Haircut', 'slug' => 'haircut', 'description' => 'A haircut', 'department_id' => $d->id,
        'category_id' => $c->id, 'price' => 1500, 'duration' => 30, 'status' => 'published'];
    $this->service = Service::create($this->serviceData + ['created_by' => $this->owner->id, 'updated_by' => $this->owner->id]);
    $this->booking = Booking::create(['salon_id' => $this->salon->id, 'service_id' => $this->service->id, 'name' => 'Client',
        'email' => 'client@example.com', 'phone' => '0700000000', 'date' => today()->addDay(), 'time' => '10:00', 'status' => 'pending']);
});

it('returns a persisted booking version usable immediately after create and update', function () {
    Sanctum::actingAs($this->owner);
    $created = $this->postJson('/api/v1/owner/bookings', ['salon_id' => $this->salon->id, 'service_id' => $this->service->id,
        'name' => 'New client', 'email' => 'client@gmail.com', 'phone' => '0700000000', 'date' => today()->addDays(2)->format('Y-m-d'), 'time' => '11:00'])
        ->assertCreated()->json('data');
    $approved = $this->patchJson('/api/v1/owner/bookings/'.$created['id'], ['version' => $created['version'], 'status' => 'approved'])
        ->assertOk()->assertJsonPath('data.status', 'approved')->json('data');
    $this->patchJson('/api/v1/owner/bookings/'.$created['id'], ['version' => $approved['version'], 'status' => 'completed'])
        ->assertOk()->assertJsonPath('data.status', 'completed');
});

it('returns named zero status counts for empty reporting periods', function () {
    Sanctum::actingAs($this->owner);
    $this->getJson('/api/v1/owner/reports')->assertOk()
        ->assertJsonPath('data.statuses.pending', 0)
        ->assertJsonPath('data.statuses.approved', 0)
        ->assertJsonPath('data.statuses.completed', 0)
        ->assertJsonPath('data.statuses.rejected', 0);
});

it('requires bearer authentication and owner role on every mobile route', function () {
    $this->getJson('/api/v1/owner/overview')->assertUnauthorized();
    Sanctum::actingAs($this->customer);
    foreach (['overview', 'salons', 'services', 'bookings', 'reports', 'subscriptions', 'lookups'] as $path) {
        $this->getJson('/api/v1/owner/'.$path)->assertForbidden();
    }
});

it('rejects unverified and revoked owners', function () {
    $this->owner->update(['email_verified_at' => null]);
    Sanctum::actingAs($this->owner);
    $this->getJson('/api/v1/owner/overview')->assertForbidden();
    $this->owner->update(['email_verified_at' => now(), 'is_approved' => false]);
    $this->getJson('/api/v1/owner/overview')->assertForbidden();
});

it('scopes lists and guessed identifiers to the signed in owner', function () {
    Sanctum::actingAs($this->owner);
    $this->getJson('/api/v1/owner/salons')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.name', 'Mine')->assertJsonMissingPath('data.0.created_by');
    $this->patchJson('/api/v1/owner/salons/'.$this->foreign->id, [])->assertNotFound();
    $foreignService = Service::create(array_merge($this->serviceData, ['slug' => 'foreign', 'created_by' => $this->other->id, 'updated_by' => $this->other->id]));
    $this->deleteJson('/api/v1/owner/services/'.$foreignService->id)->assertNotFound();
    $this->getJson('/api/v1/owner/bookings?salon_id='.$this->foreign->id)->assertNotFound();
    $this->postJson('/api/v1/owner/services', $this->serviceData + ['salon_id' => $this->foreign->id])->assertNotFound();
});

it('preserves read and renewal access but rejects management after plan expiry', function () {
    $this->subscription->update(['ends_at' => now()->subDay()]);
    Sanctum::actingAs($this->owner);
    $this->getJson('/api/v1/owner/overview')->assertOk()->assertJsonPath('data.capabilities.manage', false);
    $this->getJson('/api/v1/owner/salons')->assertOk();
    $this->getJson('/api/v1/owner/subscriptions')->assertOk();
    $this->getJson('/api/v1/owner/bookings')->assertForbidden();
    $this->postJson('/api/v1/owner/salons', [])->assertForbidden();
});

it('uses catalogue validation and ignores forged ownership and plan overrides', function () {
    Sanctum::actingAs($this->owner);
    $data = ['name' => 'New salon', 'owner_name' => 'Me', 'number_of_staff' => 1, 'show_price' => false,
        'created_by' => $this->other->id, 'updated_by' => $this->other->id, 'owner_id' => $this->other->id];
    $id = $this->postJson('/api/v1/owner/salons', $data)->assertCreated()->json('data.id');
    expect(Salon::find($id)->created_by)->toBe($this->owner->id);
    $this->postJson('/api/v1/owner/salons', $data)->assertUnprocessable();
    $this->patchJson('/api/v1/owner/salons/'.$id, array_merge($data, ['number_of_staff' => 999]))->assertUnprocessable();
    $this->postJson('/api/v1/owner/services', array_merge($this->serviceData, ['salon_id' => $id, 'slug' => 'second', 'created_by' => $this->other->id, 'updated_by' => $this->other->id]))->assertCreated();
    expect(Service::latest('id')->first()->created_by)->toBe($this->owner->id);
});

it('updates bookings with concurrency protection and rejects foreign moves', function () {
    Sanctum::actingAs($this->owner);
    $version = $this->getJson('/api/v1/owner/bookings')->assertOk()->json('data.0.version');
    $this->patchJson('/api/v1/owner/bookings/'.$this->booking->id, ['version' => $version, 'status' => 'approved'])->assertOk()->assertJsonPath('data.status', 'approved');
    $this->patchJson('/api/v1/owner/bookings/'.$this->booking->id, ['version' => $version, 'status' => 'rejected'])->assertConflict();
    $version = $this->getJson('/api/v1/owner/bookings')->json('data.0.version');
    $this->patchJson('/api/v1/owner/bookings/'.$this->booking->id, ['version' => $version, 'salon_id' => $this->foreign->id])->assertNotFound();
});

it('keeps booking history and archives services instead of removing financial records', function () {
    Sanctum::actingAs($this->owner);
    $this->deleteJson('/api/v1/owner/salons/'.$this->salon->id)->assertOk();
    $this->deleteJson('/api/v1/owner/services/'.$this->service->id)->assertOk();
    expect(Service::withTrashed()->find($this->service->id)->trashed())->toBeTrue();
    expect(Booking::find($this->booking->id))->not->toBeNull();
});

it('matches confirmed booking value and enforces the analytics plan feature', function () {
    $this->booking->update(['status' => 'approved', 'date' => today()]);
    Sanctum::actingAs($this->owner);
    $this->getJson('/api/v1/owner/reports')->assertOk()->assertJsonPath('data.value', 1500)->assertJsonPath('data.bookings', 1);
    $this->getJson('/api/v1/owner/overview')->assertOk()->assertJsonPath('data.summary.booking_value', 1500);
    $this->package->update(['analytics' => false]);
    $this->getJson('/api/v1/owner/reports')->assertForbidden();
});

it('never accepts client payment amounts or approval and prevents duplicate requests', function () {
    Sanctum::actingAs($this->owner);
    $data = ['package_id' => $this->package->id, 'payment_method' => 'bank_transfer', 'transaction_id' => 'TEST-ONLY', 'status' => 'active', 'amount_paid' => 99999, 'user_id' => $this->other->id];
    $this->postJson('/api/v1/owner/subscriptions', $data)->assertCreated()->assertJsonPath('data.status', 'pending')->assertJsonPath('data.amount_paid', '0.00');
    $this->postJson('/api/v1/owner/subscriptions', $data)->assertUnprocessable();
    expect($this->other->subscriptions()->count())->toBe(0);
});

it('validates image type size and media ownership', function () {
    Storage::fake('public');
    Sanctum::actingAs($this->owner);
    $this->postJson('/api/v1/owner/salons/'.$this->foreign->id.'/media', [])->assertNotFound();
    $this->postJson('/api/v1/owner/salons/'.$this->salon->id.'/media', ['collection' => 'gallery', 'image' => UploadedFile::fake()->create('bad.svg', 20, 'image/svg+xml')])->assertUnprocessable();
    $this->postJson('/api/v1/owner/salons/'.$this->salon->id.'/media', ['collection' => 'gallery', 'image' => UploadedFile::fake()->image('photo.jpg')->size(1025)])->assertUnprocessable();
    $this->postJson('/api/v1/owner/salons/'.$this->salon->id.'/media', ['collection' => 'gallery', 'image' => UploadedFile::fake()->image('photo.jpg', 20, 20)])->assertCreated();
    $this->getJson('/api/v1/owner/salons')->assertOk()->assertJsonCount(1, 'data.0.media');
});

it('leaves legacy retirement contracts unchanged', function () {
    Sanctum::actingAs($this->owner);
    $this->getJson('/api/owner/dashboard/stats')->assertStatus(410);
    $this->getJson('/api/salons')->assertOk();
});

it('requires current versions for booking deletion and scopes availability', function () {
    Sanctum::actingAs($this->owner);
    $this->getJson('/api/v1/owner/slots?salon_id='.$this->foreign->id.'&service_id='.$this->service->id.'&date='.today()->addDay()->format('Y-m-d'))->assertNotFound();
    $this->deleteJson('/api/v1/owner/bookings/'.$this->booking->id, ['version' => str_repeat('0', 64)])->assertConflict();
    $version = $this->getJson('/api/v1/owner/bookings')->json('data.0.version');
    $this->deleteJson('/api/v1/owner/bookings/'.$this->booking->id, ['version' => $version])->assertOk();
    expect(Booking::find($this->booking->id))->toBeNull();
});

it('enforces calendar plan permission and rejects forged gallery ordering', function () {
    Sanctum::actingAs($this->owner);
    $this->getJson('/api/v1/owner/calendar?date='.today()->addDay()->format('Y-m-d'))->assertOk()->assertJsonCount(1, 'data');
    $this->package->update(['calendar' => false]);
    $this->getJson('/api/v1/owner/calendar?date='.today()->format('Y-m-d'))->assertForbidden();
    $this->patchJson('/api/v1/owner/salons/'.$this->salon->id.'/media-order', ['ids' => [999999]])->assertUnprocessable();
    $this->patchJson('/api/v1/owner/salons/'.$this->foreign->id.'/media-order', ['ids' => []])->assertNotFound();
});
