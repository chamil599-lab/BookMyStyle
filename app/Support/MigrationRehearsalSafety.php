<?php

namespace App\Support;

use Illuminate\Database\Connection;
use RuntimeException;

final class MigrationRehearsalSafety
{
    public static function cloneConfiguration(array $source, ?string $database): array
    {
        // ConnectionFactory keeps an existing name; Migrator routes DDL by that name.
        unset($source['name']);
        $source['database'] = $database;

        return $source;
    }

    public static function protectSource(Connection $connection): void
    {
        $connection->beforeExecuting(static function (string $query): void {
            if (! preg_match('/\A\s*(?:SELECT|SHOW|DESCRIBE|EXPLAIN)\b/i', $query)
                || preg_match('/\bINTO\s+(?:OUTFILE|DUMPFILE)\b/i', $query)) {
                throw new RuntimeException('Source connection write blocked by rehearsal guard.');
            }
        });
    }
}
