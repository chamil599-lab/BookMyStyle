<?php

use App\Filament\Admin\Pages\AnalyticsDashboard;
use App\Filament\Admin\Pages\Dashboard;
use App\Filament\Admin\Resources\SubscriptionPackages\Pages\EditSubscriptionPackage;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\UserSubscriptions\Pages\ListUserSubscriptions;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\Service;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\BookingService;
use App\Services\PackageManagement;
use App\Services\SubscriptionService;
use App\Services\UserModeration;
use App\Support\BookingValue;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
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

afterEach(function () {
    $this->travelBack();
});

it('separates scheduled renewals and historical payment totals without overlapping buckets', function () {
    $make = fn ($status, $start, $end, $amount = 100) => UserSubscription::create([
        'user_id' => $this->owner->id, 'package_id' => $this->package->id, 'amount_paid' => $amount,
        'status' => $status, 'starts_at' => $start, 'ends_at' => $end]);
    $make('active', now()->addDays(10), now()->addDays(40));
    $make('active', now()->subDays(40), now()->subDay());
    $make('pending', null, now()->subDay(), 0);
    $make('canceled', now()->subDays(20), now()->addDay(), 80);
    $make('rejected', null, null, 0);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    $page = new Dashboard;
    expect($page->getSubscriptionStatsProperty())->toBe(['total' => 6, 'active' => 1, 'scheduled' => 1,
        'pending' => 1, 'expired' => 1, 'canceled' => 1, 'rejected' => 1]);
    expect((float) $page->getRevenueStatsProperty()['subscription_revenue'])->toBe(380.0);
});

it('uses appointment dates and soft deleted service prices with exact inclusive daily windows', function () {
    $this->travelTo(Carbon::parse('2026-03-31 16:00:00'));
    foreach (['2026-03-02', '2026-03-31', '2026-04-01', '2026-03-01'] as $date) {
        Booking::create(array_replace($this->data, ['date' => $date, 'status' => 'completed']));
    }
    $this->service->delete();
    $chart = BookingValue::chart(BookingValue::query([$this->salon->id]), today()->subDays(29), today());
    expect($chart['labels'])->toHaveCount(30)->and($chart['data'][0])->toBe(100.0)->and($chart['data'][29])->toBe(100.0)
        ->and(array_sum($chart['data']))->toBe(200.0);
    $monthly = BookingValue::chart(BookingValue::query(), today()->startOfMonth()->subMonthsNoOverflow(11), today(), true);
    expect($monthly['labels'])->toHaveCount(12)->and(array_unique($monthly['labels']))->toHaveCount(12)
        ->and($monthly['labels'][10])->toBe('Feb 2026')->and($monthly['data'][11])->toBe(300.0);
});

it('rejects forged analytics filters and expired or revoked feature access on live updates', function () {
    $this->package->update(['analytics' => true]);
    $other = User::factory()->create();
    $foreign = $this->salon->replicate();
    $foreign->fill(['created_by' => $other->id])->save();
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    Livewire::test(AnalyticsDashboard::class)->set('salonId', $foreign->id)->assertNotFound();
    Livewire::test(AnalyticsDashboard::class)->set('period', 'unbounded')->assertHasErrors(['period']);
    Livewire::test(AnalyticsDashboard::class)->set('profitMonth', 13)->assertHasErrors(['profitMonth']);
    $component = Livewire::test(AnalyticsDashboard::class);
    $this->package->update(['analytics' => false]);
    $component->set('period', '7d')->assertForbidden();
});

it('shows completed bookings in analytics and scopes earliest booking years to the owner', function () {
    $this->package->update(['analytics' => true]);
    Booking::create(array_replace($this->data, ['date' => today()->toDateString(), 'status' => 'completed']));
    $foreign = $this->salon->replicate();
    $foreign->fill(['created_by' => $this->admin->id])->save();
    Booking::create(array_replace($this->data, ['date' => '1999-01-01', 'salon_id' => $foreign->id, 'status' => 'completed']));
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->owner);
    $page = new AnalyticsDashboard;
    $page->mount();
    expect($page->getBookingStatsProperty()['completed'])->toBe(1)
        ->and($page->getProfitYearsProperty())->toBe([now()->year]);
});

it('edits packages with typed features calendar access and quoted requests preserved', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    $this->get('/admin/packages/'.$this->package->id.'/edit')->assertRedirect('/filament/subscription-packages/'.$this->package->id.'/edit');
    Livewire::test(EditSubscriptionPackage::class, ['record' => $this->package->id])
        ->fillForm(['price' => 250, 'calendar' => true, 'features' => ['Calendar', 'Reports']])->call('save')->assertHasNoFormErrors();
    expect($this->package->fresh()->features)->toBe(['Calendar', 'Reports'])->and($this->package->fresh()->calendar)->toBeTrue()
        ->and($pending->fresh()->features_snapshot['price'])->toBe('100.00');
    $this->actingAs($this->owner);
    $this->get('/filament/subscription-packages/'.$this->package->id.'/edit')->assertForbidden();
});

it('validates package amounts integer limits dates and feature text before saving', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    Livewire::test(EditSubscriptionPackage::class, ['record' => $this->package->id])
        ->fillForm(['price' => -1, 'duration_days' => 0, 'max_staff' => 1.5,
            'start_date' => '2026-10-10', 'end_date' => '2026-10-09', 'features' => [str_repeat('a', 256)]])
        ->call('save')->assertHasFormErrors(['price', 'duration_days', 'max_staff', 'end_date', 'features.0']);
    expect($this->package->fresh()->price)->toBe('100.00');
});

it('retains archived package history and removes automatic batch payment approval', function () {
    $this->package->delete();
    expect($this->paid->fresh()->package->name)->toBe('Basic');
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    Livewire::test(ListUserSubscriptions::class)->assertActionDoesNotExist('generate');
});

it('approves customers atomically and refuses a stale rejection or administrator edit', function () {
    $this->customer->update(['is_approved' => false]);
    $moderation = app(UserModeration::class);
    $moderation->approve($this->admin, $this->customer, 'customer');
    expect($this->customer->fresh()->is_approved)->toBeTrue()->and($this->customer->fresh()->approved_by)->toBe($this->admin->id);
    expect(fn () => $moderation->reject($this->admin, $this->customer))->toThrow(ValidationException::class);
    expect(fn () => $moderation->approve($this->admin, $this->owner, 'admin'))->toThrow(ValidationException::class);
    $this->actingAs($this->admin);
    $this->get('/filament/users/'.$this->admin->id.'/edit')->assertForbidden();
});

it('preserves stored password on unrelated user edits and records approval audit data', function () {
    $this->customer->update(['is_approved' => false]);
    $hash = $this->customer->password;
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    Livewire::test(EditUser::class, ['record' => $this->customer->id])
        ->assertFormSet(['password' => null])->fillForm(['name' => 'Changed', 'is_approved' => true])
        ->call('save')->assertHasNoFormErrors();
    expect($this->customer->fresh()->password)->toBe($hash)->and($this->customer->fresh()->approved_by)->toBe($this->admin->id);
});

it('cancels active subscriptions through the administrative action without erasing paid history', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    Livewire::test(ListUserSubscriptions::class)
        ->callTableAction('cancel', $this->paid)->assertHasNoActionErrors();
    expect($this->paid->fresh()->status)->toBe('canceled')->and($this->paid->fresh()->amount_paid)->toBe('100.00');
    $this->actingAs($this->owner);
    Livewire::test(ListUserSubscriptions::class)
        ->assertTableActionHidden('cancel', $this->paid);
});

it('grants the existing onboarding trial through native approval only once and protects established accounts from rejection', function () {
    $this->owner->update(['is_approved' => false]);
    $this->paid->delete();
    $this->package->update(['slug' => 'pro-salon']);
    $service = app(UserModeration::class);
    $service->approve($this->admin, $this->owner, 'owner');
    expect($this->owner->subscriptions()->count())->toBe(1)->and($this->owner->subscriptions()->first()->is_trial)->toBeTrue();
    $this->owner->update(['is_approved' => false]);
    expect(fn () => $service->reject($this->admin, $this->owner))->toThrow(ValidationException::class);
    expect(User::find($this->owner->id))->not->toBeNull();
});

it('blocks archiving packages referenced by pending active or scheduled requests but preserves historical names', function () {
    $management = app(PackageManagement::class);
    expect(fn () => $management->archive($this->admin, $this->package))->toThrow(ValidationException::class);
    $this->paid->update(['status' => 'canceled']);
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    expect(fn () => $management->archive($this->admin, $this->package))->toThrow(ValidationException::class);
    $pending->update(['status' => 'canceled']);
    expect($management->archive($this->admin, $this->package))->toBeTrue()->and($this->paid->fresh()->package->name)->toBe('Basic');
});

it('validates administrative user creation roles before writing any account', function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    $this->actingAs($this->admin);
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Created', 'email' => 'created@example.test', 'password' => 'StrongPassword123!',
            'role' => 'customer', 'is_approved' => true])->call('create')->assertHasNoFormErrors();
    $created = User::where('email', 'created@example.test')->firstOrFail();
    expect($created->hasRole('customer'))->toBeTrue()->and($created->approved_by)->toBe($this->admin->id)
        ->and($created->email_verified_at)->toBeNull();
    Livewire::test(CreateUser::class)
        ->fillForm(['name' => 'Forged', 'email' => 'forged@example.test', 'password' => 'StrongPassword123!',
            'role' => 'super-admin'])->call('create')->assertHasFormErrors(['role']);
    expect(User::where('email', 'forged@example.test')->exists())->toBeFalse();
});

it('enforces package availability dates when requests bypass the pricing page', function () {
    $this->package->update(['start_date' => today()->addDay(), 'end_date' => today()->addMonth()]);
    expect(fn () => $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner))
        ->toThrow(ValidationException::class);
    $this->package->update(['start_date' => today()->subMonth(), 'end_date' => today()->subDay()]);
    expect(fn () => $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner))
        ->toThrow(ValidationException::class);
    $this->package->update(['start_date' => today(), 'end_date' => today()]);
    expect($this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner)->status)->toBe('pending');
});
