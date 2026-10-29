<?php

use App\Filament\Admin\Resources\Salons\Pages\EditSalon;
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
use App\Services\SalonClosureService;
use App\Services\SubscriptionService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
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

function saveClosure($test, array $dates): void
{
    app(SalonClosureService::class)->save($test->owner, $test->salon, ['dates' => $dates, 'reason' => 'Holiday']);
}

it('blocks public management and API bookings and restores availability after reopening', function () {
    saveClosure($this, [$this->data['date']]);
    expect($this->bookings->timeSlots($this->salon, $this->data['date'], $this->service))->toBe([]);
    expect($this->bookings->remainingCapacity($this->salon, $this->data['date'], '10:00', $this->service->id))->toBe(0);
    foreach ([false, true] as $management) {
        try {
            $this->bookings->create($this->data, $management ? $this->owner : $this->customer, $management);
            $this->fail('Closed date accepted');
        } catch (ValidationException $e) {
            expect($e->errors())->toHaveKey('date');
        }
    }
    Sanctum::actingAs($this->customer);
    $this->postJson('/api/bookings', $this->data)->assertUnprocessable()->assertJsonValidationErrors('date');
    saveClosure($this, []);
    expect($this->bookings->availableSlots($this->salon, $this->data['date'], $this->service))->toContain('10:00');
    $this->bookings->create($this->data, $this->customer);
    expect(Booking::count())->toBe(1);
});

it('identifies active appointment conflicts and saves no partial closures', function () {
    $booking = $this->bookings->create($this->data);
    try {
        saveClosure($this, [$this->data['date'], today()->addDays(2)->format('Y-m-d')]);
        $this->fail('Conflict accepted');
    } catch (ValidationException $e) {
        expect($e->errors()['dates'][0])->toContain('#'.$booking->id)->toContain('Customer');
    }
    expect($this->salon->closedDates()->count())->toBe(0);
    $this->bookings->changeStatus($booking, 'rejected', $this->owner);
    saveClosure($this, [$this->data['date']]);
    expect($this->salon->isClosedOn($this->data['date']))->toBeTrue();
    expect(fn () => $this->bookings->changeStatus($booking->fresh(), 'approved', $this->owner))->toThrow(ValidationException::class);
});

it('rejects another owner and invalid or duplicate dates', function () {
    $other = User::factory()->create(['is_approved' => true]);
    $other->assignRole('owner');
    expect(fn () => app(SalonClosureService::class)->save($other, $this->salon, ['dates' => [$this->data['date']]]))->toThrow(AuthorizationException::class);
    foreach ([['bad'], [today()->subDay()->format('Y-m-d')], [$this->data['date'], $this->data['date']]] as $dates) {
        expect(fn () => saveClosure($this, $dates))->toThrow(ValidationException::class);
    }
});

it('renders owner calendar and persists its selection through the action', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    $component = Livewire::test(EditSalon::class, ['record' => $this->salon->getRouteKey()])
        ->mountAction('closedDates')->assertActionMounted('closedDates');
    expect($component->getMountedActionModalHtml())->toContain('Close date range');
    $component->setActionData(['dates' => [$this->data['date']], 'reason' => 'Holiday'])
        ->callMountedAction()->assertHasNoActionErrors();
    expect($this->salon->isClosedOn($this->data['date']))->toBeTrue();
});

it('shows closed dates and the closed message on the customer calendar', function () {
    saveClosure($this, [$this->data['date']]);
    Livewire::test(BookingForm::class, ['salonId' => $this->salon->id])
        ->set('date', $this->data['date'])->set('service_id', $this->service->id)
        ->assertSee('Salon closed on this date')->assertSee('Red dates: salon closed');
});
it('exposes date strings and closure state to API clients without private reasons', function () {
    saveClosure($this, [$this->data['date']]);
    $this->getJson('/api/salons/'.$this->salon->id.'/working-hours')
        ->assertOk()->assertJsonPath('closed_dates', [$this->data['date']])->assertDontSee('Holiday');
    $this->getJson('/api/salons/'.$this->salon->id)
        ->assertOk()->assertJsonPath('closed_dates', [$this->data['date']])->assertDontSee('Holiday');
    $this->getJson('/api/salons/'.$this->salon->id.'/booked-times?date='.$this->data['date'])
        ->assertOk()->assertJsonPath('is_closed', true);
});

it('keeps the closure modal open and shows conflicts on the calendar field', function () {
    $this->bookings->create($this->data);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    Livewire::test(EditSalon::class, ['record' => $this->salon->getRouteKey()])
        ->mountAction('closedDates')
        ->setActionData(['dates' => [$this->data['date']]])
        ->callMountedAction()->assertHasActionErrors(['dates'])->assertActionMounted('closedDates');
    expect($this->salon->isClosedOn($this->data['date']))->toBeFalse();
});
