<?php

use App\Filament\Admin\Pages\SubscriptionCheckout;
use App\Filament\Admin\Pages\SubscriptionPlans;
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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;

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

it('retires every documented React management endpoint without reading or mutating records', function () {
    $inventory = json_decode(file_get_contents(base_path('docs/migration/retired-management-api.json')), true)['endpoints'];
    $tables = ['users', 'salons', 'services', 'bookings', 'user_subscriptions', 'subscription_packages', 'categories', 'departments'];
    $before = collect($tables)->mapWithKeys(fn ($table) => [$table => DB::table($table)->orderBy('id')->get()->toJson()]);
    expect($inventory)->toHaveCount(56);
    foreach ($inventory as $endpoint) {
        Sanctum::actingAs(str_starts_with($endpoint['uri'], '/api/admin/') ? $this->admin : $this->owner);
        $uri = str_replace(['{id}', '{package}'], [$this->salon->id, $this->package->id], $endpoint['uri']);
        $response = $this->json($endpoint['method'], $uri, ['status' => 'active', 'name' => 'Injected', 'amount_paid' => 999999999]);
        $response->assertStatus(410)->assertJsonPath('code', 'endpoint_retired')
            ->assertJsonPath('next_url', route($endpoint['replacement_route']))->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        expect($response->headers->get('Cache-Control'))->toContain('no-store');
    }
    foreach ($before as $table => $records) {
        expect(DB::table($table)->orderBy('id')->get()->toJson())->toBe($records);
    }
    Mail::assertNothingSent();
    Notification::assertNothingSent();
});

it('requires authentication and the correct verified role before disclosing a retirement destination', function () {
    $this->getJson('/api/admin/users')->assertUnauthorized();
    $this->getJson('/api/owner/salons')->assertUnauthorized();
    Sanctum::actingAs($this->customer);
    $this->getJson('/api/admin/users')->assertForbidden();
    $this->getJson('/api/owner/salons')->assertForbidden();
    $this->owner->update(['email_verified_at' => null]);
    Sanctum::actingAs($this->owner);
    $this->postJson('/api/owner/salons')->assertForbidden();
    $this->admin->update(['email_verified_at' => null]);
    Sanctum::actingAs($this->admin);
    $this->getJson('/api/admin/users')->assertForbidden();
});

it('rechecks revoked approval and roles rather than trusting cached account state', function () {
    Sanctum::actingAs($this->owner);
    DB::table('users')->where('id', $this->owner->id)->update(['is_approved' => false]);
    $this->postJson('/api/owner/services')->assertForbidden();
    Sanctum::actingAs($this->admin);
    $this->admin->load('roles');
    DB::table('model_has_roles')->where('model_id', $this->admin->id)->delete();
    $this->patchJson('/api/admin/users/'.$this->customer->id.'/approve')->assertForbidden();
    expect($this->customer->fresh()->is_approved)->toBeFalse();
});

it('returns explicit retirement for broken session resource writes and nested staff service screens', function () {
    $this->actingAs($this->owner);
    foreach (['salons', 'bookings'] as $section) {
        $this->post('/owner/'.$section, ['name' => 'Injected'])->assertStatus(410)->assertSee('Your request did not change any records.');
        foreach (['PUT', 'PATCH', 'DELETE'] as $method) {
            $this->call($method, '/owner/'.$section.'/'.$this->salon->id, ['name' => 'Injected'])->assertStatus(410);
        }
    }
    foreach (['services', 'staff'] as $section) {
        foreach (['', '/create', '/1', '/1/edit'] as $suffix) {
            $this->get('/owner/salons/'.$this->salon->id.'/'.$section.$suffix)->assertStatus(410);
        }
        foreach (['POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            $this->call($method, '/owner/salons/'.$this->salon->id.'/'.$section.'/1')->assertStatus(410);
        }
    }
    expect($this->salon->fresh()->name)->toBe('Salon')->and(Booking::count())->toBe(0);
});

it('keeps management bookmarks as GET redirects and never redirects retired writes', function () {
    $this->actingAs($this->owner)->get('/owner/salons')->assertRedirect('/filament/salons');
    $this->post('/owner/salons')->assertStatus(410)->assertHeaderMissing('Location');
    $this->actingAs($this->admin)->get('/admin/packages')->assertRedirect('/filament/subscription-packages');
});

it('renders active renewal plans in LKR with local assets and only available packages', function () {
    SubscriptionPackage::create(['name' => 'Future hidden', 'slug' => 'future', 'price' => 200,
        'duration_days' => 30, 'max_salons' => 5, 'max_staff' => 5, 'max_services' => 5, 'is_active' => true, 'start_date' => today()->addDay()]);
    SubscriptionPackage::create(['name' => 'Ended hidden', 'slug' => 'ended', 'price' => 200,
        'duration_days' => 30, 'max_salons' => 5, 'max_staff' => 5, 'max_services' => 5, 'is_active' => true, 'end_date' => today()->subDay()]);
    $response = $this->actingAs($this->owner)->get(SubscriptionPlans::getUrl(panel: 'admin'))->assertOk()
        ->assertSee('Your current plan is Basic')->assertSee('LKR 100.00')->assertSee('Request renewal')
        ->assertDontSee('expired')->assertDontSee('Future hidden')->assertDontSee('Ended hidden')
        ->assertDontSee('cdn.tailwindcss.com')->assertDontSee('unpkg.com');
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $this->get('/subscription/packages')->assertRedirect(route('subscription.required'));
    $this->get(SubscriptionCheckout::getUrl(['package' => $this->package->id], panel: 'admin'))->assertOk()->assertSee('Transaction reference');
});

it('allows an expired owner to request recovery while ignoring forged owner amount status and dates', function () {
    $this->paid->update(['ends_at' => now()->subDay()]);
    $this->actingAs($this->owner)->get(SubscriptionPlans::getUrl(panel: 'admin'))->assertOk();
    $this->get(SubscriptionCheckout::getUrl(['package' => $this->package->id], panel: 'admin'))->assertOk();
    $this->post('/subscription/subscribe/'.$this->package->id, $this->payment + ['user_id' => $this->customer->id,
        'amount_paid' => 999, 'status' => 'active', 'starts_at' => today(), 'ends_at' => today()->addYear()])
        ->assertRedirect('/filament/user-subscriptions');
    $pending = $this->owner->subscriptions()->pending()->firstOrFail();
    expect($pending->amount_paid)->toBe('0.00')->and($pending->starts_at)->toBeNull()
        ->and($pending->ends_at)->toBeNull()->and($this->paid->fresh()->amount_paid)->toBe('100.00');
    $this->get(SubscriptionPlans::getUrl(panel: 'admin'))->assertSee('request is pending approval')->assertDontSee('Choose plan');
    $this->get('/subscription/checkout/'.$this->package->id)->assertRedirect(route('subscription.required'));
    $this->post('/subscription/subscribe/'.$this->package->id, $this->payment)->assertRedirect(route('subscription.required'));
    expect($this->owner->subscriptions()->pending()->count())->toBe(1);
});

it('blocks customer administrator unverified and pending owner access to native subscription requests', function () {
    foreach ([$this->customer, $this->admin] as $actor) {
        $this->actingAs($actor)->getJson('/subscription/required')->assertForbidden();
        $this->postJson('/subscription/subscribe/'.$this->package->id, $this->payment)->assertForbidden();
    }
    $this->owner->update(['email_verified_at' => null]);
    $this->actingAs($this->owner)->getJson('/subscription/checkout/'.$this->package->id)->assertForbidden();
    $this->postJson('/subscription/subscribe/'.$this->package->id, $this->payment)->assertForbidden();
    $this->owner->update(['email_verified_at' => now(), 'is_approved' => false]);
    $this->getJson('/subscription/required')->assertForbidden();
    $this->postJson('/subscription/subscribe/'.$this->package->id, $this->payment)->assertForbidden();
    expect($this->owner->subscriptions()->pending()->count())->toBe(0);
});

it('denies unavailable checkout packages and validates bank references before creating requests', function () {
    $this->actingAs($this->owner);
    $this->postJson('/subscription/subscribe/'.$this->package->id, ['payment_method' => 'bank_transfer'])
        ->assertUnprocessable()->assertJsonValidationErrors('transaction_id');
    $this->postJson('/subscription/subscribe/'.$this->package->id, ['payment_method' => 'bank_transfer', 'transaction_id' => str_repeat('A', 256)])
        ->assertUnprocessable()->assertJsonValidationErrors('transaction_id');
    $this->package->update(['is_active' => false]);
    $this->get('/subscription/checkout/'.$this->package->id)->assertNotFound();
    $this->postJson('/subscription/subscribe/'.$this->package->id, $this->payment)->assertUnprocessable();
    expect($this->owner->subscriptions()->pending()->count())->toBe(0);
});

it('denies stale owner service calls after approval is revoked', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    DB::table('users')->where('id', $this->owner->id)->update(['is_approved' => false]);
    expect(fn () => $this->subscriptions->updatePayment($pending, $this->owner, ['payment_method' => 'one_pay']))
        ->toThrow(HttpException::class);
    $pending->delete();
    expect(fn () => $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner))
        ->toThrow(HttpException::class);
});

it('denies stale administrative subscription approval cancellation and trial calls', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    DB::table('users')->where('id', $this->admin->id)->update(['email_verified_at' => null]);
    foreach ([fn () => $this->subscriptions->approve($pending, $this->admin, 100),
        fn () => $this->subscriptions->cancel($this->paid, $this->admin),
        fn () => $this->subscriptions->grantTrial($this->owner, $this->package, $this->admin)] as $action) {
        expect($action)->toThrow(HttpException::class);
    }
    expect($pending->fresh()->status)->toBe('pending')->and($this->paid->fresh()->status)->toBe('active');
});

it('rejects payment amounts outside the database precision before approving a subscription', function () {
    $pending = $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    expect(fn () => $this->subscriptions->approve($pending, $this->admin, 100000000))
        ->toThrow(ValidationException::class);
    expect($pending->fresh()->status)->toBe('pending')->and($pending->fresh()->amount_paid)->toBe('0.00');
});

it('keeps retained booking management APIs verified private and subject to shared rules', function () {
    $booking = $this->bookings->create($this->data);
    Sanctum::actingAs($this->owner);
    $response = $this->getJson('/api/salons/'.$this->salon->id.'/bookings')->assertOk();
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $this->owner->update(['email_verified_at' => null]);
    $this->getJson('/api/salons/'.$this->salon->id.'/bookings')->assertForbidden();
    $this->patchJson('/api/bookings/'.$booking->id.'/status', ['status' => 'approved'])->assertForbidden();
    expect($booking->fresh()->status)->toBe('pending');
});

it('rechecks verification in shared booking writes even when the caller holds a stale account', function ($role) {
    $actor = $this->{$role};
    $actor->load('roles');
    $booking = $this->bookings->create($this->data);
    DB::table('users')->where('id', $actor->id)->update(['email_verified_at' => null]);
    expect(fn () => $this->bookings->changeStatus($booking, 'approved', $actor))
        ->toThrow(AuthorizationException::class);
    expect(fn () => $this->bookings->create(array_replace($this->data, ['time' => '11:00']), $actor, true))
        ->toThrow(AuthorizationException::class);
    expect($booking->fresh()->status)->toBe('pending')->and(Booking::count())->toBe(1);
    Mail::assertNothingSent();
})->with(['owner', 'admin']);

it('moves subscription bookmarks into Filament and protects direct panel access', function () {
    $plans = SubscriptionPlans::getUrl(panel: 'admin');
    $checkout = SubscriptionCheckout::getUrl(['package' => $this->package->id], panel: 'admin');
    $this->actingAs($this->owner)->get('/subscription/required')->assertRedirect($plans);
    $this->get('/subscription/checkout/'.$this->package->id)->assertRedirect($checkout);
    $this->get($plans)->assertOk()->assertSee('fi-sidebar', false);
    $this->get($checkout)->assertOk()->assertSee('data-subscription-request', false);
    $this->from($checkout)->post('/subscription/subscribe/'.$this->package->id, ['payment_method' => 'bank_transfer'])
        ->assertRedirect($checkout)->assertSessionHasErrors('transaction_id');
    $this->get($checkout)->assertOk()->assertSee('transaction id field is required');
    foreach ([$this->customer, $this->admin] as $actor) {
        $this->actingAs($actor)->get($plans)->assertForbidden();
        $this->get($checkout)->assertForbidden();
    }
    $this->actingAs($this->owner);
    $this->package->update(['is_active' => false]);
    $this->get($checkout)->assertNotFound();
});

it('shows trial dates and prevents duplicate requests through the Filament checkout', function () {
    $this->paid->update(['is_trial' => true]);
    $plans = SubscriptionPlans::getUrl(panel: 'admin');
    $checkout = SubscriptionCheckout::getUrl(['package' => $this->package->id], panel: 'admin');
    $this->actingAs($this->owner)->get($plans)->assertOk()->assertSee('Free trial')->assertSee('days remaining')->assertSee('Started');
    $this->subscriptions->request($this->owner, $this->package, $this->payment, $this->owner);
    $this->get($checkout)->assertRedirect($plans);
    expect($this->owner->subscriptions()->pending()->count())->toBe(1);
});
