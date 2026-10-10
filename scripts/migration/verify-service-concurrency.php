<?php

// Synthetic fixtures in a new local MySQL database; never migrate the imported database.
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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'mysql' || ! in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('This check requires local MySQL.');
}
$database = $argv[1] ?? 'bms_service_test_'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
if (! preg_match('/^bms_service_test_[0-9]{8}_[0-9]{6}_[a-f0-9]{6}$/', $database)) {
    throw new RuntimeException('Unexpected test database name.');
}
$worker = $argv[2] ?? null;
if (! $worker) {
    DB::statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
config(['database.connections.mysql.database' => $database, 'mail.default' => 'array', 'queue.default' => 'sync']);
DB::purge('mysql');
Mail::fake();
Notification::fake();
$directory = base_path('.local/migration-baseline/service-concurrency/'.$database);
if (! is_dir($directory)) {
    mkdir($directory, 0700, true);
}

if ($worker) {
    $identity = $argv[3];
    $fixture = json_decode(file_get_contents($directory.'/fixture.json'), true, flags: JSON_THROW_ON_ERROR);
    $owner = User::findOrFail($fixture['owner_id']);
    // Signal immediately before attempting the transaction; parent holds the owner lock.
    file_put_contents($directory.'/'.$worker.'-'.$identity.'.ready', 'ready');
    try {
        if ($worker === 'booking' || $worker === 'quota') {
            $data = $fixture['booking'];
            if ($worker === 'quota') {
                $data['time'] = '11:00';
                if ($identity === '2') {
                    $data['salon_id'] = $fixture['second_salon_id'];
                }
            }
            app(BookingService::class)->create($data);
        } elseif ($worker === 'reactivate') {
            app(BookingService::class)->changeStatus(Booking::findOrFail($fixture['reactivate_ids'][(int) $identity - 1]), 'approved', $owner);
        } else {
            app(SubscriptionService::class)->request($owner, SubscriptionPackage::findOrFail($fixture['package_id']), ['payment_method' => 'one_pay'], $owner);
        }
        $result = ['outcome' => 'created'];
    } catch (ValidationException $exception) {
        $result = ['outcome' => 'rejected', 'status' => $exception->status];
    }
    file_put_contents($directory.'/'.$worker.'-'.$identity.'.json', json_encode($result, JSON_THROW_ON_ERROR));
    exit(0);
}

Artisan::call('migrate', ['--force' => true]);
Role::findOrCreate('owner', 'web');
$owner = User::factory()->create(['is_approved' => true]);
$owner->assignRole('owner');
$package = SubscriptionPackage::create(['name' => 'Concurrency fixture', 'slug' => 'fixture', 'price' => 100,
    'duration_days' => 30, 'max_salons' => 5, 'max_staff' => 5, 'max_services' => 5, 'is_active' => true]);
UserSubscription::create(['user_id' => $owner->id, 'package_id' => $package->id, 'status' => 'active',
    'amount_paid' => 100, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
$department = Department::create(['name' => 'Fixture', 'slug' => 'fixture']);
$category = Category::create(['name' => 'Fixture', 'department_id' => $department->id]);
$salon = Salon::create(['name' => 'Fixture', 'owner_name' => 'Owner', 'created_by' => $owner->id,
    'updated_by' => $owner->id, 'open_time' => '09:00', 'close_time' => '18:00', 'number_of_staff' => 1]);
$service = Service::create(['title' => 'Fixture', 'slug' => 'fixture', 'description' => 'Fixture', 'department_id' => $department->id,
    'category_id' => $category->id, 'price' => 100, 'duration' => 30, 'status' => 'published', 'created_by' => $owner->id, 'updated_by' => $owner->id]);
$fixture = ['owner_id' => $owner->id, 'package_id' => $package->id, 'booking' => ['salon_id' => $salon->id,
    'service_id' => $service->id, 'name' => 'Fixture', 'email' => 'fixture@example.com', 'phone' => '0700000000',
    'date' => today()->addDay()->format('Y-m-d'), 'time' => '10:00']];
$rejected = Booking::create($fixture['booking'] + ['status' => 'rejected']);
$otherRejected = Booking::create($fixture['booking'] + ['status' => 'rejected']);
$fixture['reactivate_ids'] = [$rejected->id, $otherRejected->id];
$secondSalon = $salon->replicate();
$secondSalon->slug = 'second-fixture';
$secondSalon->save();
$fixture['second_salon_id'] = $secondSalon->id;
file_put_contents($directory.'/fixture.json', json_encode($fixture, JSON_THROW_ON_ERROR));
$summary = ['database' => $database, 'checks' => []];
foreach (['booking', 'reactivate', 'quota', 'subscription'] as $type) {
    if ($type === 'reactivate') {
        Booking::where('status', 'pending')->delete();
    }
    if ($type === 'quota') {
        $package->update(['max_bookings_per_month' => Booking::count() + 1]);
    }
    DB::beginTransaction();
    User::whereKey($owner->id)->lockForUpdate()->firstOrFail();
    $processes = [];
    try {
        foreach ([1, 2] as $identity) {
            $log = $directory.'/'.$type.'-'.$identity.'.log';
            $process = proc_open([PHP_BINARY, __FILE__, $database, $type, (string) $identity], [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, base_path());
            if (! is_resource($process)) {
                throw new RuntimeException('Unable to start worker.');
            }
            fclose($pipes[0]);
            $processes[] = $process;
        }
        $deadline = microtime(true) + 20;
        while (! is_file($directory.'/'.$type.'-1.ready') || ! is_file($directory.'/'.$type.'-2.ready')) {
            if (microtime(true) > $deadline) {
                throw new RuntimeException('Worker readiness timed out. Inspect private logs.');
            }
            usleep(50000);
        }
        DB::commit();
        foreach ($processes as $process) {
            if (proc_close($process) !== 0) {
                throw new RuntimeException('Worker failed. Inspect private logs.');
            }
        }
    } catch (Throwable $exception) {
        if (DB::transactionLevel()) {
            DB::rollBack();
        }
        foreach ($processes as $process) {
            if (is_resource($process)) {
                proc_terminate($process);
            }
        }
        throw $exception;
    }
    $results = array_map(fn ($id) => json_decode(file_get_contents($directory.'/'.$type.'-'.$id.'.json'), true, flags: JSON_THROW_ON_ERROR), [1, 2]);
    $outcomes = array_column($results, 'outcome');
    sort($outcomes);
    if ($outcomes !== ['created', 'rejected']) {
        throw new RuntimeException('Concurrency invariant failed for '.$type);
    }
    $expectedStatus = in_array($type, ['quota', 'subscription'], true) ? 422 : 409;
    $conflict = array_values(array_filter($results, fn ($result) => $result['outcome'] === 'rejected'))[0];
    if ($conflict['status'] !== $expectedStatus) {
        throw new RuntimeException('Unexpected conflict result.');
    }
    $summary['checks'][$type] = ['passed' => true, 'results' => $results];
}
file_put_contents($directory.'/summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
echo json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
