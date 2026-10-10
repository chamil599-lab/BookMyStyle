<?php

// Read-only aggregate checks: no customer records, credentials, mail, or writes.
declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$connection = DB::connection();
$schema = $connection->getSchemaBuilder();
$tables = $schema->getTableListing($connection->getDatabaseName(), false);
$report = ['scope' => 'Local imported data; aggregate read-only checks', 'tables' => count($tables), 'counts' => []];
foreach (['users', 'salons', 'services', 'bookings', 'user_subscriptions', 'media', 'migrations'] as $table) {
    $report['counts'][$table] = $connection->table($table)->count();
}
$report['orphaned_foreign_keys'] = [];
foreach ($tables as $table) {
    foreach ($schema->getForeignKeys($table) as $key) {
        if (($key['foreign_schema'] ?? $connection->getDatabaseName()) !== $connection->getDatabaseName()) {
            continue;
        }
        $query = $connection->table($table.' as source');
        foreach ($key['columns'] as $column) {
            $query->whereNotNull('source.'.$column);
        }
        $query->whereNotExists(function ($related) use ($key) {
            $related->selectRaw('1')->from($key['foreign_table'].' as target');
            foreach ($key['columns'] as $index => $column) {
                $related->whereColumn('target.'.$key['foreign_columns'][$index], 'source.'.$column);
            }
        });
        if (($count = $query->count()) > 0) {
            $report['orphaned_foreign_keys'][] = ['table' => $table, 'constraint' => $key['name'], 'rows' => $count];
        }
    }
}
$report['missing_upload_originals'] = 0;
$report['missing_generated_conversions'] = 0;
foreach (Media::cursor() as $media) {
    if (! is_file($media->getPath())) {
        $report['missing_upload_originals']++;
    }
    foreach ($media->generated_conversions ?? [] as $conversion => $generated) {
        if ($generated && ! is_file($media->getPath($conversion))) {
            $report['missing_generated_conversions']++;
        }
    }
}
$report['non_transactional_tables'] = array_values(array_map(static fn ($table) => $table['name'], array_filter(
    $schema->getTables($connection->getDatabaseName()), static fn ($table) => strtolower($table['engine'] ?? '') !== 'innodb'
)));
file_put_contents($root.'/docs/migration/baseline/import-checks.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)."\n");
echo json_encode($report, JSON_THROW_ON_ERROR).PHP_EOL;
