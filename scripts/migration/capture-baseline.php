<?php

// Run from the project root: php scripts/migration/capture-baseline.php [mysqldump-path]
// Read-only against the configured database. Backups are private local artifacts.
declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$stamp = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Ymd-His');
$private = $root.'/.local/migration-baseline/'.$stamp;
$public = $root.'/docs/migration/baseline';
foreach ([$private, $public] as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0700, true)) {
        throw new RuntimeException('Unable to create baseline directory.');
    }
}
function writeJson(string $path, mixed $value): void
{
    if (file_put_contents($path, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n") === false) {
        throw new RuntimeException('Unable to write baseline artifact.');
    }
}
$routes = [];
foreach ($app['router']->getRoutes() as $route) {
    $routes[] = ['methods' => $route->methods(), 'uri' => $route->uri(), 'name' => $route->getName(),
        'action' => $route->getActionName(), 'middleware' => $route->gatherMiddleware()];
}
writeJson($public.'/laravel-routes.json', $routes);
$schedule = $app->make(Illuminate\Console\Scheduling\Schedule::class);
writeJson($public.'/schedule.json', array_map(static fn ($event) => [
    'description' => $event->description, 'expression' => $event->expression,
    'timezone' => $event->timezone,
], $schedule->events()));
$connection = DB::connection();
$driver = $connection->getDriverName();
$manifest = ['captured_at' => (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format(DATE_ATOM),
    'scope' => 'Configured local workspace connection; not a production backup',
    'php' => PHP_VERSION, 'laravel' => $app->version(), 'database_driver' => $driver,
    'environment' => $app->environment(), 'app_timezone' => config('app.timezone'),
    'queue_driver' => config('queue.default'), 'session_driver' => config('session.driver'),
    'mail_driver' => config('mail.default'), 'route_count' => count($routes),
    'scheduled_event_count' => count($schedule->events()), 'backup_status' => 'pending'];
$privateConfig = null;
$exitCode = 0;
foreach (['.env', 'client/.env', 'client/.env.local', 'client/.env.production'] as $relative) {
    if (is_file($root.'/'.$relative)) {
        if (!copy($root.'/'.$relative, $private.'/'.str_replace('/', '-', $relative))) {
            throw new RuntimeException('Unable to preserve local configuration.');
        }
    }
}
try {
    // Force connection before attempting a backup; no credentials or records are printed.
    $connection->getPdo();
    $schema = in_array($driver, ['mysql', 'mariadb'], true) ? $connection->getDatabaseName() : 'main';
    $tables = $connection->getSchemaBuilder()->getTableListing($schema, false);
    $counts = [];
    foreach ($tables as $table) {
        $counts[$table] = $connection->table($table)->count();
    }
    writeJson($private.'/table-counts.json', $counts);
    writeJson($public.'/database-schema.json', array_map(static fn ($table) => [
        'table' => $table,
        'columns' => $connection->getSchemaBuilder()->getColumns($table),
        'indexes' => $connection->getSchemaBuilder()->getIndexes($table),
        'foreign_keys' => $connection->getSchemaBuilder()->getForeignKeys($table),
    ], $tables));
    $manifest['table_count'] = count($tables);
    if ($driver === 'sqlite') {
        $backup = $private.'/database.sqlite';
        $connection->getPdo()->exec('VACUUM INTO '.$connection->getPdo()->quote($backup));
        $check = new PDO('sqlite:'.$backup);
        if ($check->query('PRAGMA integrity_check')->fetchColumn() !== 'ok') {
            throw new RuntimeException('SQLite backup integrity check failed.');
        }
        foreach ($counts as $table => $count) {
            $quoted = '"'.str_replace('"', '""', $table).'"';
            if ((int) $check->query('SELECT COUNT(*) FROM '.$quoted)->fetchColumn() !== $count) {
                throw new RuntimeException('SQLite backup row count mismatch.');
            }
        }
        $manifest['backup_validation'] = 'Integrity and row counts verified on independent backup connection';
    } elseif ($driver === 'mysql' || $driver === 'mariadb') {
        if (!empty($connection->getConfig('url'))) {
            throw new RuntimeException('URL-configured database requires a separately reviewed dump configuration.');
        }
        $quote = static fn ($value) => '"'.str_replace(["\\", '"', "\n", "\r"], ["\\\\", '\\"', '\\n', '\\r'], (string) $value).'"';
        $privateConfig = $private.'/dump-client.cnf';
        $config = "[client]\n";
        foreach (['host', 'port', 'username', 'password', 'unix_socket'] as $key) {
            $value = $connection->getConfig($key);
            if ($value !== null && $value !== '') {
                $option = match ($key) { 'username' => 'user', 'unix_socket' => 'socket', default => $key };
                $config .= $option.'='.$quote($value)."\n";
            }
        }
        file_put_contents($privateConfig, $config);
        @chmod($privateConfig, 0600);
        $backup = $private.'/database.sql';
        $command = [$argv[1] ?? 'mysqldump', '--defaults-extra-file='.$privateConfig,
            '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--routines', '--events', '--triggers',
            '--result-file='.$backup, (string) $connection->getDatabaseName()];
        $process = proc_open($command, [0 => ['pipe', 'r'], 1 => ['file', $private.'/dump.stdout', 'w'],
            2 => ['file', $private.'/dump.stderr', 'w']], $pipes, $root);
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to launch database dump utility.');
        }
        fclose($pipes[0]);
        if (proc_close($process) !== 0 || !is_file($backup) || filesize($backup) === 0) {
            throw new RuntimeException('Database dump failed; inspect private dump.stderr locally.');
        }
        $manifest['backup_validation'] = 'Dump exit status, size, and SHA-256 checked; isolated restore drill still required';
    } else {
        throw new RuntimeException('Unsupported backup driver; prepare a native backup before migrating.');
    }
    $manifest['backup_status'] = 'created';
    $manifest['backup_bytes'] = filesize($backup);
    $manifest['backup_sha256'] = hash_file('sha256', $backup);
    writeJson($private.'/manifest.json', $manifest);
    writeJson($public.'/environment-summary.json', $manifest);
    echo json_encode(['status' => 'created', 'private_directory' => '.local/migration-baseline/'.$stamp,
        'driver' => $driver, 'tables' => count($tables), 'routes' => count($routes)], JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    $manifest['backup_status'] = 'failed';
    writeJson($public.'/environment-summary.json', $manifest);
    // Exception text may contain connection information: keep it out of console and Git.
    file_put_contents($private.'/failure.txt', $exception->getMessage());
    fwrite(STDERR, "Baseline capture failed; inspect the ignored private failure.txt. No database writes were performed.\n");
    $exitCode = 1;
} finally {
    if ($privateConfig !== null && is_file($privateConfig)) {
        unlink($privateConfig);
    }
}
exit($exitCode);
