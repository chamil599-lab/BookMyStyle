<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\ImageCycle;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    foreach (['admin', 'owner', 'customer', 'user'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->admin = User::factory()->create(['is_approved' => true]);
    $this->admin->assignRole('admin');
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
    $this->other = User::factory()->create(['is_approved' => true]);
    $this->other->assignRole('owner');
    $this->package = SubscriptionPackage::create(['name' => 'Pro', 'slug' => 'pro-salon', 'price' => 100, 'duration_days' => 30, 'max_salons' => 3, 'max_staff' => 3, 'max_services' => 4, 'max_bookings_per_month' => 30, 'analytics' => true, 'calendar' => true, 'is_active' => true]);
    $this->subscription = UserSubscription::create(['user_id' => $this->owner->id, 'package_id' => $this->package->id, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth(), 'amount_paid' => 100]);
    $this->salon = Salon::create(['name' => 'Owner salon', 'owner_name' => 'Owner', 'created_by' => $this->owner->id, 'updated_by' => $this->owner->id, 'number_of_staff' => 2, 'open_time' => '09:00', 'close_time' => '18:00', 'show_price' => true]);
    $department = Department::create(['name' => 'Hair', 'slug' => 'hair', 'is_active' => true]);
    $category = Category::create(['name' => 'Cut', 'department_id' => $department->id, 'is_active' => true]);
    $this->service = Service::create(['title' => 'Cut', 'slug' => 'cut', 'description' => 'Cut', 'department_id' => $department->id, 'category_id' => $category->id, 'price' => 1000, 'duration' => 30, 'status' => 'published', 'created_by' => $this->owner->id, 'updated_by' => $this->owner->id]);
    $this->booking = Booking::create(['salon_id' => $this->salon->id, 'service_id' => $this->service->id, 'name' => 'Client', 'email' => 'client@example.com', 'phone' => '0700000000', 'date' => today()->addDay(), 'time' => '10:00', 'status' => 'pending']);
});

it('filters and sorts administration records using allowlisted fields only', function () {
    Sanctum::actingAs($this->admin);
    $this->getJson('/api/v1/admin/resources/users?filter[role]=owner&filter[is_approved]=1&sort=name_asc')
        ->assertOk()->assertJsonCount(2, 'data');
    $this->getJson('/api/v1/admin/resources/users?filter[role]=customer')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/admin/resources/users?filter[password]=secret')->assertUnprocessable();
    $this->getJson('/api/v1/admin/resources/users?sort=password')->assertUnprocessable();
    $this->getJson('/api/v1/admin/resources/categories?filter[department_id]=999999')->assertUnprocessable();
    $this->getJson('/api/v1/admin/resources/users/schema')->assertOk()->assertJsonStructure(['data' => ['filters']]);
});

it('exposes safe audit details and subscription snapshots to verified administrators', function () {
    Sanctum::actingAs($this->admin);
    $users = $this->getJson('/api/v1/admin/resources/users')->assertOk();
    $users->assertJsonStructure(['data' => [['created_at', 'updated_at', 'email_verified_at', 'approved_at', 'approved_by_name']]])
        ->assertJsonMissingPath('data.0.password')->assertJsonMissingPath('data.0.remember_token');
    $this->subscription->update(['features_snapshot' => ['max_salons' => 3, 'analytics' => true]]);
    $this->getJson('/api/v1/admin/subscriptions')->assertOk()
        ->assertJsonPath('data.0.features_snapshot.max_salons', 3)
        ->assertJsonPath('data.0.owner_email', $this->owner->email);
});

it('filters service catalogues while preserving owner scope', function () {
    Sanctum::actingAs($this->admin);
    $this->getJson('/api/v1/admin/services?status=published&department_id='.$this->service->department_id.'&q=Cut')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/admin/services?status=draft')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/admin/services?status=invalid')->assertUnprocessable();
    Sanctum::actingAs($this->other);
    $this->getJson('/api/v1/owner/salons?q=Owner')->assertOk()->assertJsonCount(0, 'data');
    $this->getJson('/api/v1/owner/services?q=Cut')->assertOk()->assertJsonCount(0, 'data');
});

it('denies every admin endpoint to anonymous owners and customers', function () {
    $this->getJson('/api/v1/admin/overview')->assertUnauthorized();
    Sanctum::actingAs($this->owner);
    foreach (['overview', 'lookups', 'salons', 'services', 'bookings', 'subscriptions', 'reports', 'calendar?date='.today()->format('Y-m-d'), 'resources/users', 'resources/packages/schema'] as $path) {
        $this->getJson('/api/v1/admin/'.$path)->assertForbidden();
    }
    $this->postJson('/api/v1/admin/resources/users', ['role' => 'admin'])->assertForbidden();
    $this->postJson('/api/v1/admin/subscriptions/1/approve', ['amount_paid' => 0])->assertForbidden();
});

it('rechecks verified administrator role on every request', function () {
    Sanctum::actingAs($this->admin);
    $this->getJson('/api/v1/admin/overview')->assertOk();
    $this->admin->update(['email_verified_at' => null]);
    $this->getJson('/api/v1/admin/overview')->assertForbidden();
    $this->admin->update(['email_verified_at' => now()]);
    $this->admin->syncRoles(['owner']);
    $this->getJson('/api/v1/admin/resources/users')->assertForbidden();
});

it('lists all resource schemas without exposing password hashes or tokens', function () {
    Sanctum::actingAs($this->admin);
    foreach (['users', 'packages', 'departments', 'categories', 'featured-salons', 'image-cycles', 'team-members', 'service-pages', 'deals'] as $kind) {
        $this->getJson('/api/v1/admin/resources/'.$kind.'/schema')->assertOk()->assertJsonStructure(['data' => ['title', 'fields', 'create']]);
        $this->getJson('/api/v1/admin/resources/'.$kind)->assertOk();
    }
    $this->getJson('/api/v1/admin/resources/users')->assertJsonMissingPath('data.0.password')->assertDontSee($this->admin->password, false);
    $this->getJson('/api/v1/admin/resources/unknown')->assertNotFound();
});

it('protects existing administrator accounts including the current administrator', function () {
    Sanctum::actingAs($this->admin);
    $record = collect($this->getJson('/api/v1/admin/resources/users')->json('data'))->firstWhere('id', $this->admin->id);
    $this->patchJson('/api/v1/admin/resources/users/'.$this->admin->id, ['version' => $record['version'], 'name' => 'Changed', 'email' => $this->admin->email, 'role' => 'owner', 'is_approved' => true])->assertForbidden();
    $this->postJson('/api/v1/admin/resources/users/'.$this->admin->id.'/delete', ['version' => $record['version']])->assertForbidden();
});

it('creates and edits non-admin users with allowlisted fields and preserves verification rules', function () {
    Sanctum::actingAs($this->admin);
    $r = $this->postJson('/api/v1/admin/resources/users', ['name' => 'New customer', 'email' => 'new@example.com', 'password' => 'Test-Only-Password-42', 'role' => 'customer', 'is_approved' => true, 'email_verified_at' => now(), 'approved_by' => 999])->assertCreated();
    $id = $r->json('data.id');
    $user = User::findOrFail($id);
    expect(Hash::check('Test-Only-Password-42', $user->password))->toBeTrue();
    expect($user->email_verified_at)->toBeNull();
    expect($user->approved_by)->toBe($this->admin->id);
    $this->patchJson('/api/v1/admin/resources/users/'.$id, ['version' => str_repeat('0', 64), 'name' => 'Wrong', 'email' => $user->email, 'role' => 'customer', 'is_approved' => true])->assertConflict();
});

it('uses approval and rejection services instead of unrestricted user mutation', function () {
    $pending = User::factory()->create(['is_approved' => false]);
    $pending->assignRole('owner');
    Sanctum::actingAs($this->admin);
    $record = collect($this->getJson('/api/v1/admin/resources/users')->json('data'))->firstWhere('id', $pending->id);
    $this->postJson('/api/v1/admin/resources/users/'.$pending->id.'/approve', ['version' => $record['version'], 'role' => 'owner'])->assertOk();
    expect($pending->fresh()->is_approved)->toBeTrue();
    expect($pending->subscriptions()->count())->toBe(1);
    $this->postJson('/api/v1/admin/resources/users/'.$this->owner->id.'/delete', ['version' => str_repeat('0', 64)])->assertConflict();
});

it('allows administrator catalogue and booking management without an owner subscription', function () {
    Sanctum::actingAs($this->admin);
    $this->getJson('/api/v1/admin/salons')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/admin/services')->assertOk()->assertJsonCount(1, 'data');
    $b = $this->getJson('/api/v1/admin/bookings')->assertOk()->json('data.0');
    $this->patchJson('/api/v1/admin/bookings/'.$this->booking->id, ['version' => $b['version'], 'status' => 'approved'])->assertOk();
    $this->getJson('/api/v1/admin/reports')->assertOk();
});

it('approves only the pending subscription using verified amount and server dates', function () {
    $pending = UserSubscription::create(['user_id' => $this->other->id, 'package_id' => $this->package->id, 'status' => 'pending', 'amount_paid' => 0, 'features_snapshot' => ['duration_days' => 30]]);
    Sanctum::actingAs($this->admin);
    $record = collect($this->getJson('/api/v1/admin/subscriptions?status=pending')->json('data'))->firstWhere('id', $pending->id);
    $this->postJson('/api/v1/admin/subscriptions/'.$pending->id.'/approve', ['version' => $record['version'], 'amount_paid' => 100, 'ends_at' => now()->addYears(20)])->assertOk();
    expect($pending->fresh()->ends_at->lte(now()->addDays(31)))->toBeTrue();
    $this->postJson('/api/v1/admin/subscriptions/'.$pending->id.'/approve', ['version' => $record['version'], 'amount_paid' => 100])->assertConflict();
});

it('prevents archiving subscribed packages and validates category hierarchy', function () {
    Sanctum::actingAs($this->admin);
    $r = $this->getJson('/api/v1/admin/resources/packages')->json('data.0');
    $this->postJson('/api/v1/admin/resources/packages/'.$this->package->id.'/delete', ['version' => $r['version']])->assertForbidden();
    $c = Category::first();
    $r = $this->getJson('/api/v1/admin/resources/categories')->json('data.0');
    $this->patchJson('/api/v1/admin/resources/categories/'.$c->id, ['version' => $r['version'], 'name' => $c->name, 'department_id' => $c->department_id, 'parent_id' => $c->id, 'is_active' => true])->assertUnprocessable();
});

it('manages content photos safely and restores archived content through policy', function () {
    Storage::fake('public');
    Sanctum::actingAs($this->admin);
    $r = $this->postJson('/api/v1/admin/resources/image-cycles', ['title' => 'Banner', 'order' => 0, 'status' => 1, 'upload_path' => '../../.env', 'created_by' => $this->owner->id])->assertCreated();
    $id = $r->json('data.id');
    expect(ImageCycle::find($id)->upload_path)->toBeNull();
    expect(ImageCycle::find($id)->created_by)->toBe($this->admin->id);
    $this->postJson('/api/v1/admin/resources/image-cycles/'.$id.'/photo-upload', ['image' => UploadedFile::fake()->create('payload.svg', 2, 'image/svg+xml')])->assertUnprocessable();
    $r = $this->postJson('/api/v1/admin/resources/image-cycles/'.$id.'/photo-upload', ['image' => UploadedFile::fake()->image('photo.png', 20, 20)])->assertOk();
    $this->postJson('/api/v1/admin/resources/image-cycles/'.$id.'/delete', ['version' => $r->json('data.version')])->assertOk();
    $r = $this->getJson('/api/v1/admin/resources/image-cycles?trash=only')->assertOk()->json('data.0');
    $this->postJson('/api/v1/admin/resources/image-cycles/'.$id.'/restore', ['version' => $r['version']])->assertOk();
    $this->postJson('/api/v1/admin/resources/image-cycles/'.$id.'/force-delete', ['version' => $r['version']])->assertConflict();
});

it('archives salons without losing bookings and prevents new public appointments', function () {
    Sanctum::actingAs($this->owner);
    $this->deleteJson('/api/v1/owner/salons/'.$this->salon->id)->assertOk();
    expect(Booking::find($this->booking->id))->not->toBeNull();
    $this->getJson('/api/salons/'.$this->salon->id)->assertNotFound();
    $this->getJson('/api/v1/owner/salons?archived=1')->assertOk()->assertJsonPath('data.0.archived', true);
    $this->postJson('/api/v1/owner/bookings', ['salon_id' => $this->salon->id, 'service_id' => $this->service->id, 'name' => 'New', 'email' => 'new@example.com', 'phone' => '0700000000', 'date' => today()->addDay()->format('Y-m-d'), 'time' => '11:00'])->assertUnprocessable();
    $this->postJson('/api/v1/owner/salons/'.$this->salon->id.'/restore')->assertOk();
    $this->getJson('/api/salons/'.$this->salon->id)->assertOk();
});
