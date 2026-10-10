<?php

// Explicit setup utility: creates only the configured local MySQL database, never tables.
declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (!$app->environment('local')) {
    throw new RuntimeException('This setup utility is restricted to the local environment.');
}
$name = config('database.default');
$settings = config('database.connections.'.$name);
if (!in_array($settings['driver'], ['mysql', 'mariadb'], true) || !empty($settings['url'])) {
    throw new RuntimeException('A directly configured MySQL/MariaDB connection is required.');
}
foreach (['database', 'charset', 'collation'] as $key) {
    $pattern = $key === 'database' ? '/\A[a-zA-Z0-9_-]+\z/' : '/\A[a-zA-Z0-9_]+\z/';
    if (!is_string($settings[$key]) || !preg_match($pattern, $settings[$key])) {
        throw new RuntimeException('Unexpected database identifier.');
    }
}
$database = $settings['database'];
$settings['database'] = null;
config(['database.connections.migration_setup' => $settings]);
$connection = DB::connection('migration_setup');
$exists = $connection->selectOne('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?', [$database]);
if (!$exists) {
    $connection->statement('CREATE DATABASE `'.$database.'` CHARACTER SET '.$settings['charset'].' COLLATE '.$settings['collation']);
}
echo json_encode(['database' => $database, 'status' => $exists ? 'already_exists' : 'created',
    'tables_created' => 0, 'data_imported' => false], JSON_THROW_ON_ERROR).PHP_EOL;
