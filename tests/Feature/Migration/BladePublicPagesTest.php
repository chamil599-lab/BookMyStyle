<?php

use App\Livewire\BookingForm;
use App\Livewire\Frontend\SalonActions;
use App\Models\Booking;
use App\Models\Category;
use App\Models\DealsPage;
use App\Models\Department;
use App\Models\FavoriteSalon;
use App\Models\ImageCycle;
use App\Models\Salon;
use App\Models\SalonRating;
use App\Models\Service;
use App\Models\ServicePage;
use App\Models\SiteVisit;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\BookingService;
use App\Support\PublicUrl;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Mail::fake();
    Notification::fake();
    foreach (['owner', 'customer'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->salon = Salon::create(['name' => 'Amber Studio', 'owner_name' => 'Owner', 'location' => 'Kandy', 'description' => 'Your new style.',
        'created_by' => $this->owner->id, 'updated_by' => $this->owner->id, 'number_of_staff' => 1,
        'show_price' => true, 'open_time' => '09:00', 'close_time' => '17:00']);
    $this->department = Department::create(['name' => 'Hair', 'slug' => 'hair', 'is_active' => true]);
    $category = Category::create(['name' => 'Cuts', 'department_id' => $this->department->id]);
    $this->service = Service::create(['title' => 'Signature cut', 'slug' => 'signature-cut', 'description' => 'A fresh cut.',
        'created_by' => $this->owner->id, 'updated_by' => $this->owner->id, 'department_id' => $this->department->id, 'category_id' => $category->id,
        'price' => 9999.55, 'duration' => 60, 'status' => 'published']);
    $package = SubscriptionPackage::create(['name' => 'Test', 'slug' => 'test', 'price' => 100, 'duration_days' => 30, 'max_salons' => 5, 'max_staff' => 5, 'max_services' => 5]);
    UserSubscription::create(['user_id' => $this->owner->id, 'package_id' => $package->id, 'amount_paid' => 100,
        'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
});

it('serves all public routes through Blade without a React redirect', function ($path) {
    $this->get($path)->assertOk()->assertSee('BookMyStyle')->assertSee('storefront-', false);
})->with(['/', '/salons', '/trending', '/popular', '/services', '/deals', '/about', '/terms-and-condition', '/privacy-policy']);

it('uses a local image fallback without rendering an unsafe hero source', function () {
    ImageCycle::create(['title' => 'Unsafe fixture', 'src_url' => 'javascript:alert(1)', 'status' => 1]);
    $this->get('/')->assertOk()->assertDontSee('javascript:alert(1)', false);
    $html = Blade::render('<x-storefront.image :src="$source" alt="Fixture" />', ['source' => 'data:image/svg+xml,test']);
    expect($html)->toContain('src="/images/salon-placeholder.svg"')->not->toContain('data:image/svg+xml');
});

it('renders salon services and booking with session navigation', function () {
    $this->actingAs($this->customer)->get('/salon/'.$this->salon->id)->assertOk()->assertSee('Signature cut')
        ->assertSee('My account')->assertSee('Request booking')->assertSeeLivewire(BookingForm::class)->assertSeeLivewire(SalonActions::class);
});

it('hides unapproved salons consistently from Blade and the public API', function () {
    $this->owner->update(['is_approved' => false]);
    $this->get('/salons')->assertOk()->assertDontSee('Amber Studio');
    $this->get('/salon/'.$this->salon->id)->assertNotFound();
    $this->getJson('/api/salons')->assertOk()->assertExactJson([]);
    foreach (['', '/services', '/working-hours', '/booked-times?date='.today()->addDay()->format('Y-m-d'), '/ratings'] as $suffix) {
        $this->getJson('/api/salons/'.$this->salon->id.$suffix)->assertNotFound();
    }
});

it('filters on service ownership instead of the last editor and treats search wildcards literally', function () {
    $other = User::factory()->create(['is_approved' => true]);
    $other->assignRole('owner');
    Salon::create(['name' => 'Other Studio', 'owner_name' => 'Owner', 'created_by' => $other->id, 'updated_by' => $other->id]);
    $this->service->update(['updated_by' => $other->id]);
    $this->get('/salons?department_id='.$this->department->id)->assertSee('Amber Studio')->assertDontSee('Other Studio');
    $this->get('/salons?q=Kandy')->assertSee('Amber Studio')->assertDontSee('Other Studio');
    $this->get('/salons?q=%25')->assertSee('No salons found')->assertDontSee('Amber Studio');
    $this->get('/salons?q=%27%20OR%201%3D1%20--')->assertSee('No salons found');
});

it('rejects malformed filters without trusting sort expressions', function () {
    $this->getJson('/salons?sort=name%20desc')->assertUnprocessable();
    $this->getJson('/salons?q[]=bad')->assertUnprocessable();
    $this->getJson('/salons?page=-1')->assertUnprocessable();
});

it('keeps query filters on paginated links', function () {
    for ($i = 0; $i < 13; $i++) {
        Salon::create(['name' => 'Kandy Studio '.$i, 'owner_name' => 'Owner', 'location' => 'Kandy', 'created_by' => $this->owner->id, 'updated_by' => $this->owner->id]);
    }
    $this->get('/salons?q=Kandy&sort=name')->assertOk()->assertSee('q=Kandy', false)->assertSee('page=2', false);
    $this->get('/salons?q=Kandy&sort=name&page=2')->assertOk();
});

it('does not expose hidden prices in HTML, Livewire markup or public services JSON', function () {
    $this->salon->update(['show_price' => false]);
    $this->get('/salons')->assertOk()->assertDontSee('9,999.55')->assertDontSee('9999.55')->assertSee('Contact salon for pricing');
    $this->get('/salon/'.$this->salon->id)->assertOk()->assertDontSee('9,999.55')->assertDontSee('9999.55')->assertSee('Contact salon for pricing');
    $this->getJson('/api/salons/'.$this->salon->id.'/services')->assertOk()->assertJsonMissingPath('0.price')->assertJsonMissingPath('0.created_by');
    $this->getJson('/api/salons/'.$this->salon->id)->assertOk()->assertJsonMissingPath('services.0.price');
});

it('escapes salon and review content and rejects executable social URLs', function () {
    $this->salon->update(['description' => '<script>alert(1)</script>', 'facebook_page' => 'javascript:alert(1)']);
    SalonRating::create(['salon_id' => $this->salon->id, 'email' => 'private@example.test', 'rating' => 5, 'review' => '<script>alert(2)</script>']);
    $this->get('/salon/'.$this->salon->id)->assertOk()->assertSee('&lt;script&gt;alert(1)', false)->assertDontSee('<script>alert(1)', false)
        ->assertDontSee('javascript:alert(1)', false)->assertDontSee('private@example.test')->assertSee('&lt;script&gt;alert(2)', false);
    expect(PublicUrl::image('data:image/svg+xml,test'))->toBeNull()->and(PublicUrl::image('//evil.test/image'))->toBeNull()
        ->and(PublicUrl::image('../private.txt'))->toBeNull()->and(PublicUrl::link('https://example.test'))->toBe('https://example.test');
});

it('publishes only managed service and deal content intended for public display', function () {
    ServicePage::create(['title' => 'Published service', 'status' => 'published', 'description' => 'Beauty']);
    ServicePage::create(['title' => 'Private draft', 'status' => 'draft']);
    DealsPage::create(['title' => 'Public offer', 'status' => true, 'original_price' => 100, 'discounted_price' => 50]);
    DealsPage::create(['title' => 'Hidden offer', 'status' => false]);
    $this->get('/services')->assertOk()->assertSee('Published service')->assertDontSee('Private draft');
    $this->get('/deals')->assertOk()->assertSee('Public offer')->assertDontSee('Hidden offer');
});

it('uses current capacity and full duration for available slots', function () {
    Booking::create(['salon_id' => $this->salon->id, 'service_id' => $this->service->id, 'name' => 'Booked', 'email' => 'booked@example.test',
        'phone' => '123', 'date' => today()->addDay()->format('Y-m-d'), 'time' => '09:00', 'status' => 'pending']);
    $slots = app(BookingService::class)->availableSlots($this->salon, today()->addDay()->format('Y-m-d'), $this->service);
    expect($slots)->not->toContain('09:00', '09:30', '16:30')->toContain('10:00', '16:00');
    Livewire::test(BookingForm::class, ['salonId' => $this->salon->id])->set('date', today()->addDay()->format('Y-m-d'))
        ->set('service_id', $this->service->id)->assertSee('10:00')->assertDontSee('value="09:00"', false);
});

it('allows guest booking and blocks revoked approval on later Livewire requests', function () {
    Livewire::test(BookingForm::class, ['salonId' => $this->salon->id])->set('name', 'Guest')->set('email', 'guest@example.test')
        ->set('phone', '123')->set('service_id', $this->service->id)->set('date', today()->addDay()->format('Y-m-d'))->set('time', '11:00')
        ->call('submit')->assertHasNoErrors();
    $this->assertDatabaseHas('bookings', ['email' => 'guest@example.test', 'status' => 'pending', 'user_id' => null]);
    $component = Livewire::test(BookingForm::class, ['salonId' => $this->salon->id]);
    $this->owner->update(['is_approved' => false]);
    $component->call('submit')->assertNotFound();
});

it('does not crash when availability inputs are malformed', function () {
    Livewire::test(BookingForm::class, ['salonId' => $this->salon->id])->set('date', '2027-02-31')->set('service_id', [$this->service->id])->assertOk();
});

it('does not publish or accept bookings for draft services', function () {
    $this->service->update(['status' => 'draft']);
    $this->get('/salon/'.$this->salon->id)->assertOk()->assertDontSee('Signature cut');
    $this->getJson('/api/salons/'.$this->salon->id)->assertOk()->assertJsonCount(0, 'services');
    $this->postJson('/api/bookings', ['salon_id' => $this->salon->id, 'service_id' => $this->service->id, 'name' => 'Guest',
        'email' => 'guest@gmail.com', 'phone' => '123', 'date' => today()->addDay()->format('Y-m-d'), 'time' => '10:00'])
        ->assertUnprocessable()->assertJsonValidationErrors('service_id');
});

it('ranks recent bookings separately from all-time popularity', function () {
    $second = Salon::create(['name' => 'Zebra Studio', 'owner_name' => 'Owner', 'created_by' => $this->owner->id, 'updated_by' => $this->owner->id]);
    $data = ['service_id' => $this->service->id, 'name' => 'Private Customer', 'email' => 'private-customer@example.test',
        'phone' => '123', 'date' => today()->format('Y-m-d'), 'time' => '10:00', 'status' => 'completed'];
    for ($i = 0; $i < 2; $i++) {
        Booking::create($data + ['salon_id' => $this->salon->id]);
    }
    // created_at is not publicly fillable; set historical fixture timestamps explicitly.
    Booking::where('salon_id', $this->salon->id)->update(['created_at' => now()->subDays(40)]);
    Booking::create($data + ['salon_id' => $second->id]);
    $this->get('/trending')->assertOk()->assertSeeInOrder(['Zebra Studio', 'Amber Studio'])->assertDontSee('private-customer@example.test');
    $this->get('/popular')->assertOk()->assertSeeInOrder(['Amber Studio', 'Zebra Studio']);
});

it('validates array-valued booking times instead of throwing a type error', function () {
    $this->postJson('/api/bookings', ['salon_id' => $this->salon->id, 'service_id' => $this->service->id, 'name' => 'Guest',
        'email' => 'guest@gmail.com', 'phone' => '123', 'date' => today()->addDay()->format('Y-m-d'), 'time' => ['10:00']])
        ->assertUnprocessable()->assertJsonValidationErrors('time');
    Livewire::test(BookingForm::class, ['salonId' => $this->salon->id])->set('name', 'Guest')->set('email', 'guest@gmail.com')
        ->set('phone', '123')->set('service_id', $this->service->id)->set('date', today()->addDay()->format('Y-m-d'))
        ->set('time', ['10:00'])->call('submit')->assertHasErrors(['time']);
});

it('serves a current sitemap with only public salons and excludes private paths from robots', function () {
    $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee('/salon/'.$this->salon->id, false)->assertDontSee('/dashboard', false);
    $this->owner->update(['is_approved' => false]);
    $this->get('/sitemap.xml')->assertDontSee('/salon/'.$this->salon->id, false);
    $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /filament', false)->assertSee('Sitemap:', false);
});

it('records public visits without query strings or raw network identity', function () {
    $this->get('/salons?q=private-search')->assertOk();
    $visit = SiteVisit::firstOrFail();
    expect($visit->path)->toBe('/salons')->and($visit->url)->not->toContain('private-search')
        ->and($visit->ip_hash)->not->toBe('127.0.0.1')->and(strlen($visit->session_id_hash))->toBe(64);
});

it('keeps favorites scoped to the signed-in user and never adds duplicates', function () {
    $other = User::factory()->create();
    FavoriteSalon::create(['user_id' => $other->id, 'salon_id' => $this->salon->id]);
    $component = Livewire::actingAs($this->customer)->test(SalonActions::class, ['salonId' => $this->salon->id]);
    $component->call('toggleFavorite')->assertHasNoErrors();
    $this->assertDatabaseCount('favorite_salons', 2);
    $component->call('toggleFavorite');
    $this->assertDatabaseHas('favorite_salons', ['user_id' => $other->id, 'salon_id' => $this->salon->id]);
    $this->assertDatabaseCount('favorite_salons', 1);
});

it('refuses guest favorites and unverified review actions', function () {
    Livewire::test(SalonActions::class, ['salonId' => $this->salon->id])->call('toggleFavorite')->assertForbidden();
    $this->customer->update(['email_verified_at' => null]);
    Livewire::actingAs($this->customer)->test(SalonActions::class, ['salonId' => $this->salon->id])->call('submitReview')->assertForbidden();
});

it('allows an owned completed booking review and blocks duplicates through both interfaces', function () {
    Booking::create(['salon_id' => $this->salon->id, 'service_id' => $this->service->id, 'user_id' => $this->customer->id,
        'name' => 'Customer', 'email' => $this->customer->email, 'phone' => '123', 'date' => today(), 'time' => '10:00', 'status' => 'completed']);
    Livewire::actingAs($this->customer)->test(SalonActions::class, ['salonId' => $this->salon->id])->set('rating', '5')
        ->set('review', 'Lovely')->call('submitReview')->assertRedirect(route('salon.show', $this->salon->id).'#reviews');
    Sanctum::actingAs($this->customer);
    $this->postJson('/api/salons/'.$this->salon->id.'/ratings', ['rating' => 4])->assertConflict();
    $this->assertDatabaseCount('salon_ratings', 1);
});
