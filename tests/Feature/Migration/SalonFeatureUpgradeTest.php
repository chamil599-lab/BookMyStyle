<?php

use App\Models\Salon;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Process\Process;

it('applies only the feature migrations over existing salons and preserves records on rerun', function () {
    $owner = User::factory()->create();
    $salon = Salon::create(['name' => 'Existing salon', 'owner_name' => 'Existing owner',
        'created_by' => $owner->id, 'updated_by' => $owner->id, 'location' => 'Colombo']);
    $before = $salon->getAttributes();
    unset($before['map_url']);
    $paths = [
        'database/migrations/2026_10_29_000001_create_salon_closed_dates_table.php',
        'database/migrations/2026_10_29_000002_add_map_url_to_salons_table.php',
    ];
    // Rehearse the pre-upgrade schema only in RefreshDatabase's disposable database.
    Schema::drop('salon_closed_dates');
    Schema::table('salons', fn ($table) => $table->dropColumn('map_url'));
    DB::table('migrations')->whereIn('migration', array_map(fn ($path) => pathinfo($path, PATHINFO_FILENAME), $paths))->delete();
    $otherMigrationCount = DB::table('migrations')->count();
    expect(Artisan::call('migrate', ['--path' => $paths, '--force' => true]))->toBe(0);
    expect(Schema::hasTable('salon_closed_dates'))->toBeTrue()->and(Schema::hasColumn('salons', 'map_url'))->toBeTrue();
    expect($salon->fresh()->only(array_keys($before)))->toEqual($before);
    $salon->update(['map_url' => 'https://maps.app.goo.gl/example']);
    $salon->closedDates()->create(['date' => '2026-12-25', 'reason' => 'Holiday']);
    expect(Artisan::call('migrate', ['--path' => $paths, '--force' => true]))->toBe(0);
    expect($salon->fresh()->map_url)->toBe('https://maps.app.goo.gl/example')
        ->and($salon->closedDates()->count())->toBe(1)
        ->and(DB::table('migrations')->count())->toBe($otherMigrationCount + 2)
        ->and(User::count())->toBe(1)->and(Salon::count())->toBe(1);
});

it('refuses to run the hosted update helper from an unexpected installation path', function () {
    $process = new Process([PHP_BINARY, base_path('deployment/cpanel/update-salon-features.php'), '--migrate']);
    $process->run();
    expect($process->getExitCode())->toBe(1)->and($process->getErrorOutput())->toContain('Unexpected installation path.');
});
