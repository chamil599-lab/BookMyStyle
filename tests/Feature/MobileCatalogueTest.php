<?php

use App\Models\Category;
use App\Models\Department;
use App\Models\Salon;
use App\Models\Service;
use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('owner', 'web');
    $owner = User::factory()->create(['is_approved' => true]);
    $owner->assignRole('owner');
    foreach (['Zulu', 'Alpha', '100% Studio'] as $name) {
        Salon::create(['name' => $name, 'owner_name' => 'Private owner', 'created_by' => $owner->id,
            'updated_by' => $owner->id, 'location' => 'Colombo', 'show_price' => false, 'number_of_staff' => 1]);
    }
    $department = Department::create(['name' => 'Hair', 'slug' => 'hair', 'is_active' => true]);
    $this->department = $department;
    $category = Category::create(['name' => 'Cuts', 'department_id' => $department->id]);
    Service::create(['title' => 'Balayage', 'slug' => 'balayage', 'description' => 'Colour',
        'price' => 1200, 'duration' => 30, 'status' => 'published', 'department_id' => $department->id,
        'category_id' => $category->id, 'created_by' => $owner->id, 'updated_by' => $owner->id]);
    $hiddenOwner = User::factory()->create(['is_approved' => false]);
    $hiddenOwner->assignRole('owner');
    Salon::create(['name' => 'Hidden salon', 'owner_name' => 'Hidden', 'created_by' => $hiddenOwner->id,
        'updated_by' => $hiddenOwner->id, 'number_of_staff' => 1]);
});

it('bounds pages, applies stable ordering and returns only lightweight public cards', function () {
    $first = $this->getJson('/api/v1/catalogue/salons?sort=name&per_page=2')->assertOk()
        ->assertJsonPath('meta.total', 3)->assertJsonPath('meta.last_page', 2)->json('data');
    expect(array_column($first, 'name'))->toBe(['100% Studio', 'Alpha']);
    expect(array_keys($first[0]))->toBe(['id', 'name', 'location', 'cover_url', 'rating', 'review_count', 'service_count']);
    $second = $this->getJson('/api/v1/catalogue/salons?sort=name&per_page=2&page=2')->assertOk()->json('data');
    expect(array_column($second, 'name'))->toBe(['Zulu']);
    $this->getJson('/api/v1/catalogue/salons?per_page=51')->assertUnprocessable();
    $this->getJson('/api/v1/catalogue/salons?sort=unsafe')->assertUnprocessable();
    $this->getJson('/api/v1/catalogue/salons?page=0')->assertUnprocessable();
});

it('searches location and published services and treats SQL wildcards literally', function () {
    $this->getJson('/api/v1/catalogue/salons?q=balayage')->assertOk()->assertJsonPath('meta.total', 3);
    $this->getJson('/api/v1/catalogue/salons?q=Colombo')->assertOk()->assertJsonPath('meta.total', 3);
    $this->getJson('/api/v1/catalogue/salons?q=%25')->assertOk()->assertJsonPath('meta.total', 1)->assertJsonPath('data.0.name', '100% Studio');
    $this->getJson('/api/v1/catalogue/salons?department_id='.$this->department->id)->assertOk()->assertJsonPath('meta.total', 3);
    $this->department->update(['is_active' => false]);
    $this->getJson('/api/v1/catalogue/salons?department_id='.$this->department->id)->assertOk()->assertJsonPath('meta.total', 0);
});

it('retains the legacy array endpoint and excludes archived salons', function () {
    Salon::where('name', 'Alpha')->update(['deleted_at' => now()]);
    $this->getJson('/api/v1/catalogue/salons')->assertOk()->assertJsonPath('meta.total', 2);
    $legacy = $this->getJson('/api/salons')->assertOk()->json();
    expect(array_is_list($legacy))->toBeTrue()->and(count($legacy))->toBe(2);
});
