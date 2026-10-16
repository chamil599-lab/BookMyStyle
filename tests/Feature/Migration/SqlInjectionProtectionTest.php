<?php

use App\Models\Salon;
use App\Models\User;
use App\Services\PublicCatalogue;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::findOrCreate('owner', 'web');
    $owner = User::factory()->create(['is_approved' => true]);
    $owner->assignRole('owner');
    $this->salon = Salon::create(['name' => "O'Brien %_! Studio", 'owner_name' => 'Fixture owner',
        'location' => 'Kandy', 'created_by' => $owner->id, 'updated_by' => $owner->id]);
    $hidden = User::factory()->create(['is_approved' => false]);
    $hidden->assignRole('owner');
    Salon::create(['name' => 'Hidden injection fixture', 'owner_name' => 'Fixture owner',
        'created_by' => $hidden->id, 'updated_by' => $hidden->id]);
});

it('keeps attack strings in bindings without broadening directory results', function ($payload) {
    DB::enableQueryLog();
    DB::flushQueryLog();
    foreach (['salons', 'home', 'trending', 'popular'] as $mode) {
        expect(app(PublicCatalogue::class)->directory(['q' => $payload], $mode)->total())->toBe(0);
    }
    $queries = collect(DB::getQueryLog());
    DB::disableQueryLog();
    expect($queries->contains(fn ($query) => str_contains($query['query'], $payload)))->toBeFalse()
        ->and($queries->contains(fn ($query) => in_array('%'.$payload.'%', $query['bindings'], true)))->toBeTrue();
    expect(Salon::count())->toBe(2)->and(User::count())->toBe(2);
})->with(["' OR 1=1 --", "' UNION SELECT password FROM users --", "'; DROP TABLE salons; --"]);

it('preserves quoted names and treats wildcard escape characters literally', function ($search) {
    $results = app(PublicCatalogue::class)->directory(['q' => $search]);
    expect($results->total())->toBe(1)->and($results->first()->id)->toBe($this->salon->id);
})->with(["O'Brien", '%', '_', '!']);

it('rejects injected identifiers and pagination before executing the directory', function ($filter) {
    $this->getJson('/salons?'.http_build_query($filter))->assertUnprocessable();
    expect(Salon::count())->toBe(2);
})->with([
    [['sort' => 'name desc; DROP TABLE salons; --']],
    [['department_id' => '1 OR 1=1']],
    [['page' => '1 UNION SELECT 1']],
    [['q' => ['unexpected']]],
]);

it('rejects API department expressions and binds editorial category values', function () {
    $payload = "' OR 1=1 --";
    $this->getJson('/api/salons?'.http_build_query(['department_id' => '1 OR 1=1']))->assertUnprocessable();
    foreach (['/deals', '/services'] as $path) {
        $this->get($path.'?'.http_build_query(['category' => $payload]))->assertOk();
    }
    expect(Salon::count())->toBe(2)->and(User::count())->toBe(2);
});
