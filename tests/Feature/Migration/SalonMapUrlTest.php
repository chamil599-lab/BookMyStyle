<?php

use App\Filament\Admin\Resources\Salons\Pages\EditSalon;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\BookingService;
use App\Services\CatalogueManagement;
use App\Services\SubscriptionService;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
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

it('saves and clears a map URL through the salon edit form', function () {
    $this->salon->update(['show_price' => true]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    $url = 'https://maps.app.goo.gl/example';
    Livewire::test(EditSalon::class, ['record' => $this->salon->getRouteKey()])
        ->fillForm(['map_url' => $url])->call('save')->assertHasNoFormErrors();
    expect($this->salon->fresh()->map_url)->toBe($url);
    Livewire::test(EditSalon::class, ['record' => $this->salon->getRouteKey()])
        ->fillForm(['map_url' => ''])->call('save')->assertHasNoFormErrors();
    expect($this->salon->fresh()->map_url)->toBeNull();
});

it('uses the saved map destination in the website and public API', function () {
    $url = 'https://www.google.com/maps/place/Salon?query=6.9,79.8&zoom=15';
    $this->salon->update(['map_url' => $url]);
    $this->get(route('salon.show', $this->salon->id))->assertOk()
        ->assertSee('View on map')->assertSee('href="'.e($url).'"', false);
    $this->getJson('/api/salons/'.$this->salon->id)->assertOk()->assertJsonPath('map_url', $url);
});

it('falls back to address directions and hides the link when no destination is available', function () {
    $this->salon->update(['location' => 'Colombo & Galle']);
    expect($this->salon->fresh()->map_link)->toBe('https://www.google.com/maps/search/?api=1&query=Colombo%20%26%20Galle');
    $this->salon->update(['location' => null, 'map_url' => null]);
    $this->get(route('salon.show', $this->salon->id))->assertOk()->assertDontSee('View on map');
});

it('rejects unsafe map URLs in shared validation and does not render legacy unsafe values', function () {
    $data = ['name' => 'Salon', 'owner_name' => 'Owner', 'number_of_staff' => 1, 'show_price' => true];
    foreach (['javascript:alert(1)', 'data:text/html,test', '//evil.example/map', 'not a url'] as $url) {
        expect(fn () => app(CatalogueManagement::class)->updateSalon($this->owner, $this->salon, $data + ['map_url' => $url]))->toThrow(ValidationException::class);
    }
    $this->salon->update(['map_url' => 'javascript:alert(1)', 'location' => null]);
    $this->get(route('salon.show', $this->salon->id))->assertOk()->assertDontSee('javascript:alert(1)')->assertDontSee('View on map');
});
