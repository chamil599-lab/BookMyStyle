<?php

use App\Mail\BookingStatusChanged;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    foreach (['admin', 'owner', 'customer'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->other = User::factory()->create();
    $this->other->assignRole('customer');
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    $package = SubscriptionPackage::create(['name' => 'Test', 'slug' => 'test', 'price' => 100,
        'duration_days' => 30, 'max_salons' => 5, 'max_staff' => 5, 'max_services' => 5]);
    $this->subscription = UserSubscription::create(['user_id' => $this->owner->id, 'package_id' => $package->id,
        'amount_paid' => 100, 'status' => 'active', 'starts_at' => now(), 'ends_at' => now()->addMonth()]);
    $department = Department::create(['name' => 'Hair', 'slug' => 'hair']);
    $category = Category::create(['name' => 'Cut', 'department_id' => $department->id]);
    $this->salon = Salon::create(['name' => 'Test Salon', 'owner_name' => 'Owner',
        'created_by' => $this->owner->id, 'updated_by' => $this->owner->id]);
    $this->service = Service::create(['title' => 'Cut', 'slug' => 'cut', 'description' => 'Cut',
        'department_id' => $department->id, 'category_id' => $category->id, 'price' => 100,
        'status' => 'published', 'created_by' => $this->owner->id, 'updated_by' => $this->owner->id]);
    $this->booking = Booking::create(['salon_id' => $this->salon->id, 'service_id' => $this->service->id,
        'user_id' => $this->customer->id, 'name' => 'Customer', 'email' => $this->customer->email,
        'phone' => '0700000000', 'date' => today()->addDay(), 'time' => '10:00', 'status' => 'pending']);
});

it('requires login for all legacy private booking and review entry points', function () {
    $this->getJson('/api/salons/'.$this->salon->id.'/bookings')->assertUnauthorized();
    $this->postJson('/api/bookings/my-bookings', ['email' => $this->customer->email])->assertUnauthorized();
    $this->patchJson('/api/bookings/'.$this->booking->id.'/status', ['status' => 'approved'])->assertUnauthorized();
    $this->getJson('/api/salons/'.$this->salon->id.'/can-rate?email='.$this->customer->email)->assertUnauthorized();
    $this->postJson('/api/salons/'.$this->salon->id.'/ratings', ['rating' => 5])->assertUnauthorized();
});

it('does not let a customer manage booking status or list a salons customer records', function () {
    Sanctum::actingAs($this->customer);
    $this->patchJson('/api/bookings/'.$this->booking->id.'/status', ['status' => 'approved'])->assertForbidden();
    $this->getJson('/api/salons/'.$this->salon->id.'/bookings')->assertForbidden();
    expect($this->booking->fresh()->status)->toBe('pending');
    Mail::assertNothingSent();
});

it('allows the active approved owner to manage their booking and sends one email', function () {
    Sanctum::actingAs($this->owner);
    $this->patchJson('/api/bookings/'.$this->booking->id.'/status', ['status' => 'approved'])->assertOk();
    $this->getJson('/api/salons/'.$this->salon->id.'/bookings')->assertOk()->assertJsonCount(1);
    expect($this->booking->fresh()->status)->toBe('approved');
    Mail::assertSent(BookingStatusChanged::class, 1);
});

it('allows an administrator without a subscription to manage bookings', function () {
    Sanctum::actingAs($this->admin);
    $this->patchJson('/api/bookings/'.$this->booking->id.'/status', ['status' => 'approved'])->assertOk();
    $this->getJson('/api/salons/'.$this->salon->id.'/bookings')->assertOk();
});

it('rejects an owner trying to access another owners salon', function () {
    $this->salon->update(['created_by' => $this->admin->id]);
    Sanctum::actingAs($this->owner);
    $this->patchJson('/api/bookings/'.$this->booking->id.'/status', ['status' => 'approved'])->assertForbidden();
    $this->getJson('/api/salons/'.$this->salon->id.'/bookings')->assertForbidden();
    expect(Gate::forUser($this->owner)->allows('update', $this->booking))->toBeFalse();
});

it('rejects an unapproved or expired owner even on the generic endpoint', function () {
    $this->owner->update(['is_approved' => false]);
    Sanctum::actingAs($this->owner);
    $this->patchJson('/api/bookings/'.$this->booking->id.'/status', ['status' => 'approved'])->assertForbidden();
    $this->owner->update(['is_approved' => true]);
    $this->subscription->update(['ends_at' => now()->subDay()]);
    $this->owner->unsetRelation('activeSubscription');
    $this->patchJson('/api/bookings/'.$this->booking->id.'/status', ['status' => 'approved'])->assertForbidden();
});

it('ignores arbitrary email in private booking lookup', function () {
    Sanctum::actingAs($this->other);
    $this->postJson('/api/bookings/my-bookings', ['email' => $this->customer->email])->assertOk()->assertExactJson([]);
    Sanctum::actingAs($this->customer);
    $this->postJson('/api/bookings/my-bookings', ['email' => $this->other->email])->assertOk()->assertJsonCount(1);
});

it('links only verified guest email and never an account owned booking by email', function () {
    $this->booking->update(['user_id' => null, 'email' => $this->other->email]);
    $this->other->update(['email_verified_at' => null]);
    Sanctum::actingAs($this->other);
    $this->postJson('/api/bookings/my-bookings')->assertOk()->assertExactJson([]);
    $this->other->update(['email_verified_at' => now()]);
    $this->postJson('/api/bookings/my-bookings')->assertOk()->assertJsonCount(1);
    $this->booking->update(['user_id' => $this->customer->id]);
    $this->postJson('/api/bookings/my-bookings')->assertOk()->assertExactJson([]);
});

it('clears email verification on a profile email change and prevents guest booking claims', function () {
    $this->booking->update(['user_id' => null]);
    // Use a fresh address for the guest record, not an existing users unique email.
    $this->booking->update(['email' => 'guest@example.com']);
    Sanctum::actingAs($this->other);
    $this->putJson('/api/customer/profile', ['name' => 'Other', 'email' => 'guest@example.com'])->assertOk();
    expect($this->other->fresh()->email_verified_at)->toBeNull();
    $this->postJson('/api/bookings/my-bookings')->assertOk()->assertExactJson([]);
});

it('ignores another customers email when checking or posting a review', function () {
    $this->booking->update(['status' => 'approved']);
    Sanctum::actingAs($this->other);
    $this->getJson('/api/salons/'.$this->salon->id.'/can-rate?email='.$this->customer->email)
        ->assertOk()->assertJsonPath('can_rate', false);
    $this->postJson('/api/salons/'.$this->salon->id.'/ratings', ['email' => $this->customer->email, 'rating' => 5])->assertForbidden();
    $this->assertDatabaseCount('salon_ratings', 0);
});

it('accepts the booking owners review without publishing email and blocks duplicates', function () {
    $this->booking->update(['status' => 'approved']);
    Sanctum::actingAs($this->customer);
    $this->getJson('/api/salons/'.$this->salon->id.'/can-rate')->assertOk()->assertJsonPath('can_rate', true);
    $this->postJson('/api/salons/'.$this->salon->id.'/ratings', ['email' => $this->other->email, 'rating' => 5, 'review' => 'Good'])
        ->assertCreated()->assertJsonMissingPath('rating.email');
    $this->assertDatabaseHas('salon_ratings', ['email' => $this->customer->email, 'rating' => 5]);
    $this->getJson('/api/salons/'.$this->salon->id.'/ratings')->assertOk()->assertJsonMissingPath('ratings.0.email');
    $this->postJson('/api/salons/'.$this->salon->id.'/ratings', ['rating' => 4])->assertConflict()->assertJsonMissingPath('existing_rating.email');
});

it('does not allow customers to change the public homepage image cycle', function () {
    Sanctum::actingAs($this->customer);
    $this->postJson('/api/image-cycles', ['title' => 'Injected'])->assertForbidden();
});

it('throttles repeated password login attempts', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/auth/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertUnprocessable();
    }
    $this->postJson('/api/auth/login', ['email' => 'missing@example.com', 'password' => 'wrong'])->assertTooManyRequests();
});

it('does not let untrusted forwarded addresses bypass login throttling', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->withHeaders(['X-Forwarded-For' => '198.51.100.'.($attempt + 1)])
            ->postJson('/api/auth/login', ['email' => 'spoofed@example.com', 'password' => 'wrong'])
            ->assertUnprocessable();
    }
    $this->withHeaders(['X-Forwarded-For' => '198.51.100.99'])
        ->postJson('/api/auth/login', ['email' => 'spoofed@example.com', 'password' => 'wrong'])
        ->assertTooManyRequests();
});

it('keeps model field protection enabled and rejects injected record identifiers', function () {
    expect(Model::isUnguarded())->toBeFalse();
    $originalId = $this->booking->id;
    $this->booking->fill(['id' => 99999, 'status' => 'approved']);
    expect($this->booking->id)->toBe($originalId)->and($this->booking->status)->toBe('approved');
});

it('does not show another owners salons or services in Filament booking forms', function () {
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    $otherSalon = Salon::create(['name' => 'Other salon', 'owner_name' => 'Other',
        'created_by' => $this->admin->id, 'updated_by' => $this->admin->id]);
    $component = \Livewire\Livewire::test(\App\Filament\Admin\Resources\Bookings\Pages\EditBooking::class, ['record' => $this->booking->id]);
    $component->fillForm(['salon_id' => $otherSalon->id])->call('save')->assertHasFormErrors(['salon_id']);
    expect($this->booking->fresh()->salon_id)->toBe($this->salon->id);
    $otherService = $this->service->replicate();
    $otherService->created_by = $this->admin->id;
    $otherService->updated_by = $this->admin->id;
    $otherService->slug = 'other-service';
    $otherService->save();
    $component->fillForm(['salon_id' => $this->salon->id, 'service_id' => $otherService->id])
        ->call('save')->assertHasFormErrors(['service_id']);
    expect($this->booking->fresh()->service_id)->toBe($this->service->id);
});

it('denies a Filament booking page for a different owner', function () {
    \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
    $this->salon->update(['created_by' => $this->admin->id]);
    $this->actingAs($this->owner);
    \Livewire\Livewire::test(\App\Filament\Admin\Resources\Bookings\Pages\EditBooking::class, ['record' => $this->booking->id])
        ->assertSuccessful();
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
