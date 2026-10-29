<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Upload as private ops/update-salon-features.php; never put this in public_html.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

ini_set('display_errors', '0');
ini_set('memory_limit', '512M');
$root = dirname(__DIR__);
$mode = $argv[1] ?? '--check';
if (! in_array($mode, ['--check', '--down', '--migrate', '--prepare', '--resume'], true)) {
    fwrite(STDERR, "Unknown update mode.\n");
    exit(1);
}
if (realpath($root) !== '/home/limachlk/bookmystyle') {
    fwrite(STDERR, "Unexpected installation path.\n");
    exit(1);
}
$reportDir = $root.'/storage/app/deployment';
if (! is_dir($reportDir) && ! mkdir($reportDir, 0750, true)) {
    exit(1);
}
$lock = fopen($reportDir.'/salon-features-update.lock', 'c');
if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
    exit;
}
$report = ['update' => 'salon-features-2026-10-29', 'mode' => $mode, 'status' => 'failed', 'steps' => []];
$exitCode = 1;
try {
    if (PHP_VERSION_ID < 80400 || PHP_VERSION_ID >= 80500 || ! is_file($root.'/.env')) {
        throw new RuntimeException('Unexpected runtime.');
    }
    $loader = require $root.'/vendor/autoload.php';
    if ($loader->isClassMapAuthoritative()) {
        throw new RuntimeException('PSR-4 fallback is required for the added classes.');
    }
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
    if (! $app->environment('production') || config('app.debug')
        || rtrim((string) config('app.url'), '/') !== 'https://bookmystyle.limach.lk'
        || config('database.default') !== 'mysql'
        || realpath($app->publicPath()) !== '/home/limachlk/public_html/bookmystyle.limach.lk') {
        throw new RuntimeException('Unexpected production configuration.');
    }
    $key = (string) config('app.key');
    $key = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
    if (! is_string($key) || ! Encrypter::supported($key, config('app.cipher'))) {
        throw new RuntimeException('Existing application key is invalid.');
    }
    DB::connection()->getPdo();
    if (! Schema::hasTable('migrations') || ! Schema::hasTable('salons')) {
        throw new RuntimeException('An existing installation is required.');
    }
    $report['steps'][] = 'existing_production_installation_checked';
    $manifest = json_decode(file_get_contents($root.'/ops/salon-features-manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    if (($manifest['update'] ?? null) !== $report['update']) {
        throw new RuntimeException('Unexpected update manifest.');
    }
    $verify = static function (array $files, string $base): void {
        if ($files === []) {
            throw new RuntimeException('Empty update manifest.');
        }
        foreach ($files as $relative => $hash) {
            if (! preg_match('~^[a-zA-Z0-9_./-]+$~', $relative) || str_contains($relative, '..')
                || str_starts_with($relative, '/') || ! is_file($base.'/'.$relative)
                || ! hash_equals($hash, hash_file('sha256', $base.'/'.$relative))) {
                throw new RuntimeException('Update file verification failed.');
            }
        }
    };
    $call = static function (string $command, array $arguments = []) use ($kernel, &$report): void {
        $report['running_step'] = $command;
        if ($kernel->call($command, $arguments) !== 0) {
            throw new RuntimeException('Update command failed.');
        }
        $report['steps'][] = $command;
        unset($report['running_step']);
    };
    $migrations = [
        'database/migrations/2026_10_29_000001_create_salon_closed_dates_table.php',
        'database/migrations/2026_10_29_000002_add_map_url_to_salons_table.php',
    ];
    foreach ($migrations as $file) {
        $hash = $manifest['migrations'][$file] ?? null;
        if (! is_string($hash) || ! is_file($root.'/'.$file) || ! hash_equals($hash, hash_file('sha256', $root.'/'.$file))) {
            throw new RuntimeException('Migration verification failed.');
        }
    }
    $report['pending_feature_migrations'] = array_values(array_filter(array_map(
        fn ($file) => pathinfo($file, PATHINFO_FILENAME), $migrations
    ), fn ($name) => ! DB::table('migrations')->where('migration', $name)->exists()));
    if ($mode === '--down') {
        $call('down', ['--retry' => 60]);
    }
    if (in_array($mode, ['--migrate', '--prepare'], true) && ! $app->isDownForMaintenance()) {
        throw new RuntimeException('Enter maintenance mode before updating.');
    }
    if ($mode === '--migrate') {
        // Only these two forward migrations; never initialize, seed, reset, or rotate keys.
        $call('migrate', ['--path' => $migrations, '--force' => true, '--no-interaction' => true]);
    }
    if ($mode === '--prepare') {
        if (! Schema::hasTable('salon_closed_dates') || ! Schema::hasColumn('salons', 'map_url')) {
            throw new RuntimeException('Feature migrations must complete first.');
        }
        $verify($manifest['application'], $root);
        $verify($manifest['public'], $app->publicPath());
        $report['steps'][] = 'installed_update_files_verified';
        foreach (['config:clear', 'route:clear', 'view:clear', 'config:cache', 'route:cache', 'view:cache'] as $command) {
            $call($command);
        }
        $call('up');
    }
    if ($mode === '--resume') {
        // Recovery only: restore the previous source/assets before invoking this mode.
        foreach (['config:clear', 'route:clear', 'view:clear', 'config:cache', 'route:cache', 'view:cache', 'up'] as $command) {
            $call($command);
        }
    }
    $report['maintenance'] = $app->isDownForMaintenance();
    $report['status'] = 'completed';
    $exitCode = 0;
} catch (Throwable $error) {
    $report['error_type'] = $error::class;
    $report['guidance'] = 'Stop this cron. Check the current mode, target runtime/configuration, maintenance status and uploaded file hashes privately. Do not reset the database or change APP_KEY. Review private Laravel logs for details.';
}
$report['finished_at_utc'] = gmdate('c');
file_put_contents($reportDir.'/salon-features-update-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo 'Salon features '.$mode.': '.$report['status'].". Read private storage/app/deployment/salon-features-update-report.json.\n";
exit($exitCode);
