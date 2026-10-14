<?php

// Local rehearsal only: restore a checked backup into a NEW database, then migrate/rollback.
declare(strict_types=1);

use App\Support\MigrationRehearsalSafety;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$directory = null;
$clientFile = null;
$exitCode = 0;
try {
    $app = require $root.'/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();
    $source = DB::connection();
    $settings = $source->getConfig();
    if (! $app->environment('local') || $settings['driver'] !== 'mysql' || ! empty($settings['url'])
        || ! in_array($settings['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
        throw new RuntimeException('Only local, directly configured loopback MySQL is supported.');
    }
    MigrationRehearsalSafety::protectSource($source);
    foreach (['TRIGGERS' => 'TRIGGER_SCHEMA', 'ROUTINES' => 'ROUTINE_SCHEMA', 'EVENTS' => 'EVENT_SCHEMA'] as $catalog => $column) {
        $objects = $source->selectOne('SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.'.$catalog.' WHERE '.$column.' = ?', [$source->getDatabaseName()]);
        if ((int) $objects->total !== 0) {
            throw new RuntimeException('Executable database objects require separate backup and rehearsal review.');
        }
    }
    $allowed = realpath($root.'/.local/migration-baseline');
    $quote = static fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
    $options = "[client]\n";
    foreach (['host' => 'host', 'port' => 'port', 'username' => 'user', 'password' => 'password', 'unix_socket' => 'socket'] as $key => $option) {
        if (isset($settings[$key]) && $settings[$key] !== '') {
            $options .= $option.'='.$quote($settings[$key])."\n";
        }
    }
    if (($argv[1] ?? '') === '--fresh') {
        if ($allowed === false) {
            throw new RuntimeException('Private migration directory missing.');
        }
        $directory = $allowed.'/index-backup-'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
        mkdir($directory, 0700);
        $clientFile = $directory.'/client.cnf';
        file_put_contents($clientFile, $options);
        @chmod($clientFile, 0600);
        $process = proc_open([$argv[2] ?? 'mysqldump', '--defaults-extra-file='.$clientFile,
            '--single-transaction', '--skip-lock-tables', '--no-tablespaces', '--skip-triggers', '--skip-events', '--set-gtid-purged=OFF',
            '--result-file='.$directory.'/database.sql', $settings['database']],
            [0 => ['pipe', 'r'], 1 => ['file', $directory.'/dump.stdout', 'w'], 2 => ['file', $directory.'/dump.stderr', 'w']], $pipes, $root);
        if (! is_resource($process)) {
            throw new RuntimeException('Cannot start fresh backup.');
        }
        fclose($pipes[0]);
        if (proc_close($process) !== 0 || ! is_file($directory.'/database.sql') || filesize($directory.'/database.sql') === 0) {
            throw new RuntimeException('Fresh backup failed.');
        }
        unlink($clientFile);
        $clientFile = null;
        file_put_contents($directory.'/manifest.json', json_encode(['backup_sha256' => hash_file('sha256', $directory.'/database.sql')], JSON_THROW_ON_ERROR));
        $backup = realpath($directory);
        $mysqlExecutable = $argv[3] ?? 'mysql';
    } else {
        $backup = realpath($argv[1] ?? '');
        $mysqlExecutable = $argv[2] ?? 'mysql';
    }
    if ($backup === false || $allowed === false || ! str_starts_with(strtolower($backup), strtolower($allowed.DIRECTORY_SEPARATOR))) {
        throw new RuntimeException('Backup must be inside the private migration directory.');
    }
    $manifest = json_decode(file_get_contents($backup.'/manifest.json'), true, flags: JSON_THROW_ON_ERROR);
    $dump = $backup.'/database.sql';
    if (! hash_equals($manifest['backup_sha256'], hash_file('sha256', $dump))) {
        throw new RuntimeException('Backup checksum mismatch.');
    }
    $sql = file_get_contents($dump);
    if (preg_match('/^\s*(?:USE\s|CREATE\s+DATABASE\b)/mi', $sql)
        || preg_match('/\bCREATE\b[^;]*\b(?:EVENT|TRIGGER|PROCEDURE|FUNCTION)\b/i', $sql)
        || preg_match('/\b(?:GTID_PURGED|SQL_LOG_BIN|GLOBAL\s*[.])\b/i', $sql)) {
        throw new RuntimeException('Dump switches or executable database objects need separate review.');
    }
    unset($sql);
    $names = ['2026_04_29_000001_add_customer_dashboard_indexes_to_bookings_table', '2026_05_17_000001_add_dashboard_performance_indexes'];
    $paths = array_map(fn ($name) => $root.'/database/migrations/'.$name.'.php', $names);
    // A cloned Laravel config contains the original connection's "name". The
    // migrator uses getName() to route Schema calls, so clones must not inherit it.
    $snapshot = static function ($connection): array {
        $schema = $connection->getSchemaBuilder();
        $tables = $schema->getTableListing($connection->getDatabaseName(), false);
        sort($tables);
        $result = [];
        foreach ($tables as $table) {
            $hashes = [];
            foreach ($connection->table($table)->cursor() as $row) {
                $hashes[] = hash('sha256', serialize((array) $row));
            }
            sort($hashes);
            $foreignKeys = $schema->getForeignKeys($table);
            foreach ($foreignKeys as &$key) {
                if (($key['foreign_schema'] ?? null) === $connection->getDatabaseName()) {
                    $key['foreign_schema'] = 'self';
                }
            }
            unset($key);
            $result[$table] = ['rows' => count($hashes), 'records' => hash('sha256', implode('', $hashes)),
                'columns' => $schema->getColumns($table), 'indexes' => $schema->getIndexes($table), 'foreign_keys' => $foreignKeys];
        }

        return $result;
    };
    $before = $snapshot($source);
    $stamp = date('Ymd_His').'_'.bin2hex(random_bytes(3));
    $target = 'bms_index_check_'.$stamp;
    $directory = $allowed.'/index-check-'.$stamp;
    mkdir($directory, 0700);
    foreach (['charset', 'collation'] as $encoding) {
        if (! preg_match('/\A[a-zA-Z0-9_]+\z/', (string) $settings[$encoding])) {
            throw new RuntimeException('Unexpected encoding identifier.');
        }
    }
    $setup = MigrationRehearsalSafety::cloneConfiguration($settings, null);
    config(['database.connections.index_setup' => $setup]);
    $server = DB::connection('index_setup');
    if ($server->selectOne('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$target])) {
        throw new RuntimeException('Target exists; refusing to overwrite.');
    }
    $server->statement('CREATE DATABASE `'.$target.'` CHARACTER SET '.$settings['charset'].' COLLATE '.$settings['collation']);
    file_put_contents($directory.'/target.txt', $target."\n");
    $clientFile = $directory.'/client.cnf';
    file_put_contents($clientFile, $options);
    @chmod($clientFile, 0600);
    $process = proc_open([$mysqlExecutable, '--defaults-extra-file='.$clientFile, '--binary-mode', $target],
        [0 => ['file', $dump, 'r'], 1 => ['file', $directory.'/import.stdout', 'w'], 2 => ['file', $directory.'/import.stderr', 'w']], $pipes, $root);
    if (! is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Isolated restore failed.');
    }
    $copyConfig = MigrationRehearsalSafety::cloneConfiguration($settings, $target);
    config(['database.connections.index_validation' => $copyConfig, 'mail.default' => 'array', 'cache.default' => 'array', 'session.driver' => 'array', 'queue.default' => 'sync']);
    $copy = DB::connection('index_validation');
    if ($copy->getName() !== 'index_validation') {
        throw new RuntimeException('Copy connection name does not match its routing key.');
    }
    $restored = $snapshot($copy);
    if ($copy->getDatabaseName() === $source->getDatabaseName() || $restored !== $before) {
        $differences = [];
        foreach ($before as $table => $state) {
            foreach ($state as $field => $value) {
                if (($restored[$table][$field] ?? null) !== $value) {
                    $differences[$table][] = $field;
                }
            }
        }
        file_put_contents($directory.'/restore-differences.json', json_encode($differences, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        throw new RuntimeException('Backup copy differs from current source; capture a fresh backup.');
    }
    $migrator = $app->make('migrator');
    $migrator->setConnection('index_validation');
    $pending = array_values(array_diff(array_keys($migrator->getMigrationFiles([$root.'/database/migrations'])), $migrator->getRepository()->getRan()));
    sort($pending);
    if ($pending !== $names) {
        throw new RuntimeException('Unexpected pending migrations; refusing rehearsal.');
    }
    $assert = static function (bool $condition, string $message): void {
        if (! $condition) {
            throw new RuntimeException($message);
        }
    };
    $migrator->run($paths, ['step' => false]);
    $up = $snapshot($copy);
    $expected = [
        'bookings' => ['bookings_user_date_status_index' => ['user_id', 'date', 'status'], 'bookings_email_date_status_index' => ['email', 'date', 'status'],
            'bookings_salon_status_date_idx' => ['salon_id', 'status', 'date'], 'bookings_status_created_idx' => ['status', 'created_at'], 'bookings_status_date_idx' => ['status', 'date']],
        'user_subscriptions' => ['user_subs_status_ends_idx' => ['status', 'ends_at']],
    ];
    if (isset($before['site_visits']) && ! array_filter($before['site_visits']['indexes'], fn ($index) => $index['columns'] === ['occurred_at'])) {
        $expected['site_visits'] = ['site_visits_occurred_at_idx' => ['occurred_at']];
    }
    foreach ($expected as $table => $indexes) {
        foreach ($indexes as $name => $columns) {
            $matches = array_filter($up[$table]['indexes'], fn ($index) => $index['name'] === $name && $index['columns'] === $columns && ! $index['unique']);
            $assert(count($matches) === 1, 'Expected index definition missing.');
        }
    }
    foreach ($before as $table => $state) {
        if ($table !== 'migrations') {
            $assert($up[$table]['rows'] === $state['rows'] && $up[$table]['records'] === $state['records'], 'Migration changed records.');
        }
        $assert($up[$table]['columns'] === $state['columns'] && $up[$table]['foreign_keys'] === $state['foreign_keys'], 'Migration changed columns or foreign keys.');
        $originalIndexes = array_values(array_filter($up[$table]['indexes'], fn ($index) => ! isset($expected[$table][$index['name']])));
        $assert($originalIndexes === $state['indexes'], 'Unexpected existing index change.');
    }
    $migrator->run($paths);
    $assert($snapshot($copy) === $up, 'Second migrate changed state.');
    $migrator->rollback($paths, ['step' => 2]);
    $assert($snapshot($copy) === $before, 'Rollback did not restore schema and records.');
    $migrator->run($paths);
    $reapplied = $snapshot($copy);
    unset($up['migrations'], $reapplied['migrations']);
    $assert($reapplied === $up, 'Reapplication differs from first migration.');
    $assert($snapshot($source) === $before, 'Source changed during rehearsal.');
    $report = ['status' => 'passed', 'artifact_directory' => basename($directory), 'tables_verified' => count($before), 'migrations_verified' => 2, 'indexes_verified' => array_sum(array_map('count', $expected)),
        'backup_matches_source' => true, 'rows_preserved' => true, 'columns_and_foreign_keys_preserved' => true,
        'second_migrate_noop' => true, 'rollback_restores_baseline' => true, 'reapply_verified' => true, 'source_unchanged' => true,
        'scope' => 'Local isolated copy only; no production deployment or source migration'];
    file_put_contents($directory.'/summary.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    if ($directory !== null) {
        file_put_contents($directory.'/failure.txt', $exception->getMessage());
    } elseif (is_dir($root.'/.local/migration-baseline')) {
        file_put_contents($root.'/.local/migration-baseline/index-check-initialization-failure.txt', $exception->getMessage());
    }
    fwrite(STDERR, "Index rehearsal failed; inspect private artifacts. Source migration was not requested.\n");
    $exitCode = 1;
} finally {
    if ($clientFile !== null && is_file($clientFile)) {
        unlink($clientFile);
    }
}
exit($exitCode);
