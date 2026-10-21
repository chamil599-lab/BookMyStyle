<?php

declare(strict_types=1);
use App\Services\FreshInstallationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Private CLI entry point for temporary cPanel Cron Jobs. Never a browser installer.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

ini_set('display_errors', '0');
ini_set('memory_limit', '512M');
$root = dirname(__DIR__);
$mode = $argv[1] ?? '--check';
if (! in_array($mode, ['--check', '--new-key', '--migrate', '--initialize', '--prepare'], true)) {
    fwrite(STDERR, "Unknown setup mode.\n");
    exit(1);
}
foreach (['bootstrap/cache', 'storage/app/private', 'storage/app/public', 'storage/app/deployment',
    'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $dir) {
    if (! is_dir($root.'/'.$dir) && ! mkdir($root.'/'.$dir, 0750, true)) {
        fwrite(STDERR, "Cannot create private runtime directories.\n");
        exit(1);
    }
}
$lock = fopen($root.'/storage/app/deployment/setup.lock', 'c');
if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
    exit;
}
$report = ['mode' => $mode, 'php' => PHP_VERSION, 'status' => 'failed', 'steps' => []];
$code = 1;
try {
    if (PHP_VERSION_ID < 80400 || PHP_VERSION_ID >= 80500) {
        throw new RuntimeException('PHP 8.4 required for this prepared release.');
    }
    $required = ['pdo_mysql', 'gd'];
    $lockData = json_decode(file_get_contents($root.'/composer.lock'), true, 512, JSON_THROW_ON_ERROR);
    foreach ($lockData['packages'] as $package) {
        foreach (array_keys($package['require'] ?? []) as $requirement) {
            if (str_starts_with($requirement, 'ext-')) {
                $required[] = substr($requirement, 4);
            }
        }
    }
    $report['missing_extensions'] = array_values(array_filter(array_unique($required), fn ($ext) => ! extension_loaded($ext)));
    if ($report['missing_extensions'] || ! is_file($root.'/.env')) {
        throw new RuntimeException('Missing extension or private environment file.');
    }
    require $root.'/vendor/autoload.php';
    $app = require $root.'/bootstrap/app.php';
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();
    if (config('app.env') !== 'production' || config('app.debug')
        || rtrim((string) config('app.url'), '/') !== 'https://bookmystyle.limach.lk'
        || config('database.default') !== 'mysql'
        || ! is_dir('/home/limachlk/public_html/bookmystyle.limach.lk')
        || realpath($app->publicPath()) !== realpath('/home/limachlk/public_html/bookmystyle.limach.lk')) {
        throw new RuntimeException('Production configuration does not match the target.');
    }
    DB::connection()->getPdo();
    $report['steps'][] = 'configuration_and_database_connection_checked';
    $call = static function (string $command, array $arguments = []) use ($kernel, &$report): void {
        $report['running_step'] = $command;
        if ($kernel->call($command, $arguments) !== 0) {
            throw new RuntimeException('Setup command failed.');
        }
        $report['steps'][] = $command;
        unset($report['running_step']);
    };
    $marker = $root.'/storage/app/deployment/'.ltrim($mode, '-').'.done';
    if ($mode !== '--check' && is_file($marker)) {
        $report['status'] = 'already_completed';
    } else {
        if ($mode === '--new-key') {
            if (! config('app.key')) {
                if (Schema::hasTable('users')
                    && DB::table('users')->exists()) {
                    throw new RuntimeException('Cannot initialize a key over existing accounts.');
                }
                $call('key:generate', ['--force' => true]);
            } else {
                $report['steps'][] = 'existing_key_preserved';
            }
        } else {
            $key = (string) config('app.key');
            $key = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
            if (! is_string($key) || ! Encrypter::supported($key, config('app.cipher'))) {
                throw new RuntimeException('Initialize the application key first.');
            }
        }
        if ($mode === '--migrate') {
            $call('migrate', ['--force' => true]);
        }
        if ($mode === '--initialize') {
            $settingsPath = $root.'/ops/admin.json';
            $settings = json_decode(file_get_contents($settingsPath), true, 512, JSON_THROW_ON_ERROR);
            app(FreshInstallationService::class)->initialize($settings);
            unlink($settingsPath);
            $report['steps'][] = 'admin_roles_and_subscription_packages_created_without_demo_accounts';
        }
        if ($mode === '--prepare') {
            if (DIRECTORY_SEPARATOR === '/') {
                chmod($root.'/storage', 0751);
                chmod($root.'/storage/app', 0751);
                chmod($root.'/storage/app/public', 0755);
            }
            foreach (['config:clear', 'route:clear', 'view:clear', 'package:discover', 'filament:assets',
                'storage:link', 'config:cache', 'route:cache', 'view:cache'] as $command) {
                if ($command === 'storage:link' && is_link($app->publicPath('storage'))) {
                    if (realpath($app->publicPath('storage')) !== realpath($app->storagePath('app/public'))) {
                        throw new RuntimeException('Unexpected existing media link.');
                    }

                    continue;
                }
                $call($command);
            }
        }
        if ($mode !== '--check') {
            file_put_contents($marker, 'completed');
        }
        $report['status'] = 'completed';
    }
    $code = 0;
} catch (Throwable $error) {
    $report['error_type'] = $error::class;
    $report['guidance'] = 'Review PHP version/extensions, private .env, database credentials and directory permissions. No success is claimed.';
}
file_put_contents($root.'/storage/app/deployment/setup-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo 'Setup '.$mode.': '.$report['status'].". Read private storage/app/deployment/setup-report.json.\n";
exit($code);
