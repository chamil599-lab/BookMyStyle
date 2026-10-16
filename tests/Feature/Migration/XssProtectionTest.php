<?php

use App\Models\Category;
use App\Models\DealsPage;
use App\Models\Department;
use App\Models\Salon;
use App\Models\SalonRating;
use App\Models\Service;
use App\Models\ServicePage;
use App\Models\User;
use App\Support\PublicUrl;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

function assertNoXssProbeMarkup(string $html): void
{
    $document = new DOMDocument;
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $xpath = new DOMXPath($document);
    expect($xpath->query('//*[@data-xss-probe or @onerror or @onload or @onfocus or @onclick]')->length)->toBe(0);
    expect($xpath->query('//script[contains(., "window.__bmsXss")]')->length)->toBe(0);
}

beforeEach(function () {
    foreach (['owner', 'customer', 'admin'] as $role) {
        Role::findOrCreate($role, 'web');
    }
    $this->payload = '<img data-xss-probe="1" src="x" onerror="window.__bmsXss=1"><script>window.__bmsXss=2</script>';
    $this->owner = User::factory()->create(['is_approved' => true]);
    $this->owner->assignRole('owner');
    $this->customer = User::factory()->create();
    $this->customer->assignRole('customer');
    $this->salon = Salon::create(['name' => 'XSS test salon', 'owner_name' => 'Fixture owner', 'location' => 'Kandy', 'created_by' => $this->owner->id,
        'updated_by' => $this->owner->id, 'show_price' => true]);
});

it('escapes stored salon service department and review payloads', function () {
    $this->salon->update(['name' => $this->payload, 'description' => $this->payload, 'location' => $this->payload,
        'facebook_page' => 'javascript:window.__bmsXss=1']);
    $department = Department::create(['name' => $this->payload, 'slug' => 'xss-test', 'is_active' => true]);
    $category = Category::create(['name' => $this->payload, 'department_id' => $department->id]);
    Service::create(['title' => $this->payload, 'slug' => 'xss-test', 'description' => $this->payload,
        'department_id' => $department->id, 'category_id' => $category->id, 'price' => 100, 'duration' => 30, 'status' => 'published',
        'created_by' => $this->owner->id, 'updated_by' => $this->owner->id]);
    SalonRating::create(['salon_id' => $this->salon->id, 'email' => 'fixture@example.test', 'rating' => 5, 'review' => $this->payload]);
    foreach (['/', '/salons', '/salon/'.$this->salon->id] as $url) {
        $response = $this->get($url)->assertOk()->assertSee(e($this->payload), false)
            ->assertDontSee('href="javascript:', false);
        assertNoXssProbeMarkup($response->getContent());
    }
});

it('encodes reflected search input in HTML and Alpine expression contexts', function () {
    $payload = "');window.__bmsXss=1;//<img data-xss-probe=1 onerror=alert(1)>";
    foreach (['/', '/salons', '/trending', '/popular'] as $url) {
        $response = $this->get($url.'?q='.rawurlencode($payload))->assertOk();
        assertNoXssProbeMarkup($response->getContent());
        expect($response->getContent())->toContain('\\u0027');
    }
});

it('escapes editorial card fields and rejects executable image sources', function () {
    DealsPage::create(['title' => $this->payload, 'description' => $this->payload, 'category' => $this->payload,
        'salon' => $this->payload, 'features' => [$this->payload], 'badge' => $this->payload, 'status' => 1,
        'src_url' => 'javascript:window.__bmsXss=1', 'original_price' => 100, 'discounted_price' => 50]);
    ServicePage::create(['title' => $this->payload, 'description' => $this->payload, 'features' => [$this->payload],
        'category_name' => $this->payload, 'status' => 'published', 'price' => 100, 'duration' => 30]);
    foreach (['/deals', '/services'] as $url) {
        $response = $this->get($url)->assertOk()->assertSee(e($this->payload), false);
        assertNoXssProbeMarkup($response->getContent());
    }
});

it('escapes customer identity and notification content', function () {
    $this->customer->update(['name' => $this->payload]);
    $this->customer->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'xss-fixture',
        'data' => ['title' => $this->payload, 'message' => $this->payload, 'url' => 'javascript:window.__bmsXss=1']]);
    foreach (['/dashboard', '/dashboard/notifications', '/profile'] as $url) {
        $response = $this->actingAs($this->customer)->get($url)->assertOk()->assertSee('window.__bmsXss');
        if ($url !== '/profile') {
            $response->assertSee(e($this->payload), false);
        }
        assertNoXssProbeMarkup($response->getContent());
    }
});

it('escapes stored content in Filament tables and the management inbox', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $this->salon->update(['name' => $this->payload]);
    $admin->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'xss-fixture',
        'data' => ['title' => $this->payload, 'message' => $this->payload, 'url' => 'javascript:window.__bmsXss=1']]);
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    foreach (['/filament/salons', '/filament/notification-inbox'] as $url) {
        $response = $this->actingAs($admin)->get($url)->assertOk()->assertSee(e($this->payload), false);
        assertNoXssProbeMarkup($response->getContent());
    }
});

it('rejects executable and obfuscated public URL schemes', function (string $url) {
    expect(PublicUrl::link($url))->toBeNull()->and(PublicUrl::image($url))->toBeNull();
})->with(['javascript:alert(1)', 'JaVaScRiPt:alert(1)', "java\tscript:alert(1)", 'data:image/svg+xml,<svg onload=alert(1)>',
    'vbscript:msgbox(1)', '//evil.example/test', '/\\evil.example/test']);
