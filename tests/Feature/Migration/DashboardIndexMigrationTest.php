<?php

use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\Schema;

it('preserves existing site-visit indexes and rolls back only indexes it creates', function (bool $existingIndex) {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec('CREATE TABLE bookings (salon_id INTEGER, status TEXT, date TEXT, created_at TEXT)');
    $pdo->exec('CREATE TABLE user_subscriptions (status TEXT, ends_at TEXT)');
    $pdo->exec('CREATE TABLE site_visits (occurred_at TEXT)');
    if ($existingIndex) {
        $pdo->exec('CREATE INDEX original_occurred_at_index ON site_visits (occurred_at)');
    }
    $connection = new SQLiteConnection($pdo);
    $original = Schema::getFacadeRoot();
    Schema::swap($connection->getSchemaBuilder());
    try {
        $migration = require database_path('migrations/2026_05_17_000001_add_dashboard_performance_indexes.php');
        $migration->up();
        $indexes = Schema::getIndexes('site_visits');
        expect($indexes)->toHaveCount(1)
            ->and($indexes[0]['name'])->toBe($existingIndex ? 'original_occurred_at_index' : 'site_visits_occurred_at_idx');
        $migration->down();
        expect(Schema::getIndexes('site_visits'))->toHaveCount($existingIndex ? 1 : 0)
            ->and(Schema::getIndexes('bookings'))->toBe([])
            ->and(Schema::getIndexes('user_subscriptions'))->toBe([]);
    } finally {
        Schema::swap($original);
    }
})->with([true, false]);
