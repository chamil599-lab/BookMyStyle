<?php

// php scripts/migration/verify-restore.php <backup-directory> <mysql-executable>
// Creates a NEW local verification database; never imports into the source database.
declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! $app->environment('local')) {
    throw new RuntimeException('Restore verification is restricted to the local environment.');
}
$directory = realpath($argv[1] ?? '');
$allowed = realpath($root.'/.local/migration-baseline');
if ($directory === false || $allowed === false || ! str_starts_with(strtolower($directory), strtolower($allowed.DIRECTORY_SEPARATOR))) {
    throw new RuntimeException('Choose a backup directory within .local/migration-baseline.');
}
$dump = $directory.'/database.sql';
$manifestPath = $directory.'/manifest.json';
$manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
if (! hash_equals($manifest['backup_sha256'], hash_file('sha256', $dump))) {
    throw new RuntimeException('Backup checksum mismatch.');
}
$sql = file_get_contents($dump);
if (preg_match('/^\s*(?:USE\s|CREATE\s+DATABASE\b)/mi', $sql)
    || preg_match('/\bCREATE\b[^;]*\bEVENT\b/i', $sql)) {
    throw new RuntimeException('Dump database switches or scheduled events require separate review.');
}
unset($sql);
$source = DB::connection();
$settings = $source->getConfig();
if (! in_array($settings['driver'], ['mysql', 'mariadb'], true) || ! empty($settings['url'])
    || ! in_array($settings['host'], ['localhost', '127.0.0.1', '::1'], true)) {
    throw new RuntimeException('Only a directly configured loopback MySQL server is supported.');
}
foreach (['charset', 'collation'] as $identifier) {
    if (! preg_match('/\A[a-zA-Z0-9_]+\z/', (string) $settings[$identifier])) {
        throw new RuntimeException('Unexpected database encoding identifier.');
    }
}
$target = 'bms_restore_'.(new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format('Ymd_His').'_'.bin2hex(random_bytes(3));
$serverConfig = $settings;
$serverConfig['database'] = null;
config(['database.connections.restore_setup' => $serverConfig]);
$server = DB::connection('restore_setup');
if ($server->selectOne('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$target])) {
    throw new RuntimeException('Restore target already exists; refusing to overwrite.');
}
$server->statement('CREATE DATABASE `'.$target.'` CHARACTER SET '.$settings['charset'].' COLLATE '.$settings['collation']);
file_put_contents($directory.'/restore-target.txt', $target."\n");
$clientConfig = $directory.'/restore-client.cnf';
$exitCode = 0;
try {
    $quote = static fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
    $config = "[client]\n";
    foreach (['host', 'port', 'username', 'password', 'unix_socket'] as $key) {
        if (isset($settings[$key]) && $settings[$key] !== '') {
            $option = match ($key) {
                'username' => 'user', 'unix_socket' => 'socket', default => $key
            };
            $config .= $option.'='.$quote($settings[$key])."\n";
        }
    }
    if (file_put_contents($clientConfig, $config) === false) {
        throw new RuntimeException('Cannot write private client configuration.');
    }
    @chmod($clientConfig, 0600);
    $process = proc_open([$argv[2] ?? 'mysql', '--defaults-extra-file='.$clientConfig, '--binary-mode', $target],
        [0 => ['file', $dump, 'r'], 1 => ['file', $directory.'/restore.stdout', 'w'],
            2 => ['file', $directory.'/restore.stderr', 'w']], $pipes, $root);
    if (! is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Restore import failed; inspect private restore.stderr.');
    }
    $settings['database'] = $target;
    config(['database.connections.migration_restore' => $settings]);
    $restored = DB::connection('migration_restore');
    $sourceTables = $source->getSchemaBuilder()->getTableListing($source->getDatabaseName(), false);
    $restoreTables = $restored->getSchemaBuilder()->getTableListing($target, false);
    sort($sourceTables);
    sort($restoreTables);
    if ($sourceTables !== $restoreTables) {
        throw new RuntimeException('Restored table list differs from source.');
    }
    $results = [];
    $schemaSignature = static function ($connection, $table): array {
        $schema = $connection->getSchemaBuilder();
        $foreignKeys = array_map(static function ($key) use ($connection) {
            if (($key['foreign_schema'] ?? null) === $connection->getDatabaseName()) {
                $key['foreign_schema'] = 'self';
            }

            return $key;
        }, $schema->getForeignKeys($table));

        return [$schema->getColumns($table), $schema->getIndexes($table), $foreignKeys];
    };
    $fingerprint = static function ($connection, $table): array {
        // Hash each record without publishing it; sorting hashes supports composite/no-primary-key tables.
        $hashes = [];
        foreach ($connection->table($table)->cursor() as $row) {
            $hashes[] = hash('sha256', serialize((array) $row));
        }
        sort($hashes);

        return ['rows' => count($hashes), 'sha256' => hash('sha256', implode('', $hashes))];
    };
    foreach ($sourceTables as $table) {
        if ($schemaSignature($source, $table) !== $schemaSignature($restored, $table)) {
            throw new RuntimeException('Restored schema differs for table '.$table);
        }
        $original = $fingerprint($source, $table);
        $copy = $fingerprint($restored, $table);
        if ($original !== $copy) {
            throw new RuntimeException('Restored records differ for table '.$table.'; ensure source is idle during capture.');
        }
        $results[$table] = $copy;
    }
    $manifest['restore_status'] = 'verified';
    $manifest['restore_verified_at'] = (new DateTimeImmutable('now', new DateTimeZone('Asia/Colombo')))->format(DATE_ATOM);
    $manifest['restore_database'] = $target;
    $manifest['backup_validation'] = 'SHA-256, isolated MySQL import, all table schemas, row counts and record fingerprints verified';
    file_put_contents($directory.'/restore-table-fingerprints.json', json_encode($results, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    $summary = $manifest;
    unset($summary['restore_database']);
    file_put_contents($root.'/docs/migration/baseline/environment-summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
    echo json_encode(['status' => 'verified', 'tables' => count($results), 'restore_database' => $target,
        'source_modified' => false], JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    file_put_contents($directory.'/restore-failure.txt', $exception->getMessage());
    fwrite(STDERR, "Restore verification failed; inspect private restore-failure.txt. Source database was not modified.\n");
    $exitCode = 1;
} finally {
    if (is_file($clientConfig)) {
        unlink($clientConfig);
    }
}
exit($exitCode);
