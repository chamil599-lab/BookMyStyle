<?php

use App\Filament\Admin\Resources\Salons\Pages\CreateSalon;
use App\Filament\Admin\Resources\Salons\Pages\EditSalon;
use App\Filament\Admin\Resources\Services\Pages\CreateService;
use App\Filament\Admin\Resources\Services\Pages\EditService;
use App\Filament\Admin\Resources\Services\ServiceResource;
use App\Filament\Admin\Widgets\BookingCalendarWidget;
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
use Filament\Facades\Filament;
use Guava\Calendar\ValueObjects\FetchInfo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
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

it('denies owners direct admin create and edit routes', function ($slug, $record) {
    $this->actingAs($this->owner);
    $this->get('/filament/'.$slug.'/create')->assertForbidden();
    if ($record) {
        $this->get('/filament/'.$slug.'/'.$this->{$record}->id.'/edit')->assertForbidden();
    }
})->with([['users', 'customer'], ['subscription-packages', null], ['departments', null], ['categories', null],
    ['image-cycles', null], ['service-pages', null], ['deals-pages', null], ['team-members', null], ['featured-salons', null]]);

it('shows admins all services and scopes owners to their own records', function () {
    $other = User::factory()->create(['is_approved' => true]);
    $other->assignRole('owner');
    $foreignService = $this->service->replicate();
    $foreignService->fill(['title' => 'Foreign service', 'slug' => 'foreign', 'created_by' => $other->id])->save();
    $foreignSalon = $this->salon->replicate();
    $foreignSalon->fill(['name' => 'Foreign salon', 'created_by' => $other->id])->save();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    expect(ServiceResource::getEloquentQuery()->pluck('id')->all())->toBe([$this->service->id]);
    $this->get('/filament/services/'.$foreignService->id.'/edit')->assertNotFound();
    $this->get('/filament/salons/'.$foreignSalon->id.'/edit')->assertNotFound();
    $this->actingAs($this->admin);
    expect(ServiceResource::getEloquentQuery()->count())->toBe(2);
    $this->get('/filament/services')->assertOk()->assertSee('Foreign service');
});

it('creates salons through Filament without trusting hidden audit fields', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    Livewire::test(CreateSalon::class)
        ->fillForm(['name' => 'New salon', 'owner_name' => 'Owner', 'number_of_staff' => 2, 'show_price' => true,
            'created_by' => $this->admin->id, 'updated_by' => $this->admin->id])
        ->call('create')->assertHasNoFormErrors();
    $salon = Salon::where('name', 'New salon')->firstOrFail();
    expect($salon->created_by)->toBe($this->owner->id)->and($salon->updated_by)->toBe($this->owner->id);
});

it('lets admins create a salon for an approved owner without needing a personal plan', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    Livewire::test(CreateSalon::class)
        ->fillForm(['name' => 'Admin salon', 'owner_name' => 'Owner', 'owner_id' => $this->owner->id,
            'number_of_staff' => 2, 'show_price' => true])->call('create')->assertHasNoFormErrors();
    $salon = Salon::where('name', 'Admin salon')->firstOrFail();
    expect($salon->created_by)->toBe($this->owner->id)->and($salon->updated_by)->toBe($this->admin->id);
});

it('creates and edits services through Filament with preserved ownership and validated categories', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    $data = $this->service->only(['title', 'slug', 'description', 'department_id', 'category_id', 'price', 'duration', 'status']);
    $data['title'] = 'Native service';
    $data['slug'] = 'native-service';
    $data['salon_id'] = $this->salon->id;
    Livewire::test(CreateService::class)
        ->fillForm($data)->call('create')->assertHasNoFormErrors();
    $service = Service::where('title', 'Native service')->firstOrFail();
    $this->actingAs($this->admin);
    Livewire::test(EditService::class, ['record' => $service->id])
        ->fillForm(['title' => 'Edited service', 'created_by' => $this->admin->id])->call('save')->assertHasNoFormErrors();
    expect($service->fresh()->created_by)->toBe($this->owner->id)->and($service->fresh()->updated_by)->toBe($this->admin->id);
});

it('enforces plan limits with field errors instead of exceptions in Filament', function () {
    $this->package->update(['max_salons' => 1, 'max_services' => 1]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    Livewire::test(CreateSalon::class)
        ->fillForm(['name' => 'Over quota', 'owner_name' => 'Owner', 'number_of_staff' => 1, 'show_price' => true])
        ->call('create')->assertHasFormErrors(['name']);
    $data = $this->service->only(['title', 'slug', 'description', 'department_id', 'category_id', 'price', 'duration', 'status']) + ['salon_id' => $this->salon->id];
    Livewire::test(CreateService::class)
        ->fillForm($data)->call('create')->assertHasFormErrors(['salon_id']);
    expect(Salon::count())->toBe(1)->and(Service::count())->toBe(1);
});

it('preserves salon ownership on edit and enforces staff limits', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    Livewire::test(EditSalon::class, ['record' => $this->salon->id])
        ->fillForm(['number_of_staff' => 6])->call('save')->assertHasFormErrors(['number_of_staff']);
    $this->actingAs($this->admin);
    Livewire::test(EditSalon::class, ['record' => $this->salon->id])
        ->fillForm(['name' => 'Admin edit', 'created_by' => $this->admin->id])->call('save')->assertHasNoFormErrors();
    expect($this->salon->fresh()->created_by)->toBe($this->owner->id)->and($this->salon->fresh()->updated_by)->toBe($this->admin->id);
});

it('allows expired owners to read their catalogue and subscription recovery but blocks mutations', function () {
    $this->paid->update(['ends_at' => now()->subMinute()]);
    $this->actingAs($this->owner)->get('/filament/salons')->assertOk();
    $this->get('/filament/services')->assertOk();
    $this->get('/filament/user-subscriptions')->assertOk();
    $this->get('/filament/salons/create')->assertForbidden();
    $this->get('/filament/services/'.$this->service->id.'/edit')->assertForbidden();
    expect(Gate::forUser($this->owner)->allows('delete', $this->salon))->toBeFalse();
});

it('protects administrator accounts from individual and bulk deletion', function () {
    expect(Gate::forUser($this->admin)->allows('delete', $this->admin))->toBeFalse();
    expect(Gate::forUser($this->admin)->allows('delete', $this->customer))->toBeTrue();
    expect(Gate::forUser($this->owner)->allows('create', User::class))->toBeFalse();
});

it('redirects legacy dashboard URLs into native destinations with role gates', function () {
    $this->actingAs($this->admin)->get('/admin')->assertRedirect(route('filament.admin.pages.dashboard'));
    $this->get('/admin/users')->assertRedirect('/filament/users');
    $this->get('/owner')->assertForbidden();
    $this->actingAs($this->owner)->get('/owner')->assertRedirect(route('filament.admin.pages.owner-dashboard'));
    $this->get('/owner/dashboard')->assertRedirect(route('filament.admin.pages.owner-dashboard'));
    $this->get('/owner/salons')->assertRedirect('/filament/salons');
    $this->get('/owner/services')->assertRedirect('/filament/services');
    $this->get('/owner/bookings/'.$this->salon->id.'/edit')->assertRedirect('/filament/bookings/'.$this->salon->id.'/edit');
    $this->get('/owner/subscription')->assertRedirect('/filament/user-subscriptions');
    $this->get('/admin')->assertForbidden();
    $this->owner->update(['is_approved' => false]);
    $this->get('/owner')->assertRedirect(route('filament.admin.pages.pending-approval'));
});

it('restricts calendar event resolution to owned bookings and the enabled plan feature', function () {
    $this->package->update(['calendar' => true]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    $booking = Booking::create($this->data + ['status' => 'pending', 'user_id' => $this->customer->id]);
    $otherSalon = $this->salon->replicate();
    $otherSalon->fill(['created_by' => $this->admin->id])->save();
    $foreign = Booking::create(array_replace($this->data, ['salon_id' => $otherSalon->id, 'status' => 'pending']));
    $widget = app(BookingCalendarWidget::class);
    $events = $widget->getEvents(new FetchInfo(['startStr' => today()->format('Y-m-d'), 'endStr' => today()->addDays(7)->format('Y-m-d')]));
    expect($events)->toHaveCount(1);
    Livewire::test(BookingCalendarWidget::class)->call('onEventClickJs',
        ['event' => ['extendedProps' => ['model' => User::class, 'key' => $this->customer->id]]])->assertForbidden();
    expect(fn () => Livewire::test(BookingCalendarWidget::class)->call('onEventClickJs',
        ['event' => ['extendedProps' => ['model' => Booking::class, 'key' => $foreign->id]]]))
        ->toThrow(ModelNotFoundException::class);
    $this->package->update(['calendar' => false]);
    $this->owner->unsetRelations();
    Livewire::test(BookingCalendarWidget::class)->assertForbidden();
});

it('rejects broad calendar fetches and stale catalogue edits after approval is revoked', function () {
    $this->package->update(['calendar' => true]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    Livewire::test(BookingCalendarWidget::class)->call('getEventsJs',
        ['startStr' => today()->format('Y-m-d'), 'endStr' => today()->addYear()->format('Y-m-d')])->assertStatus(422);
    $component = Livewire::test(EditService::class, ['record' => $this->service->id])
        ->fillForm(['title' => 'Forbidden edit']);
    $this->owner->update(['is_approved' => false]);
    $component->call('save')->assertForbidden();
    expect($this->service->fresh()->title)->toBe('Cut');
});
