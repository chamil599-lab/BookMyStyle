<?php

use App\Support\MigrationRehearsalSafety;
use Illuminate\Container\Container;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\SQLiteConnection;

it('removes inherited connection names so migration DDL routes to the copy', function () {
    $settings = ['driver' => 'sqlite', 'database' => ':memory:', 'name' => 'source', 'prefix' => ''];
    $factory = new ConnectionFactory(new Container);
    expect($factory->make($settings, 'copy')->getName())->toBe('source');
    $copy = $factory->make(MigrationRehearsalSafety::cloneConfiguration($settings, ':memory:'), 'copy');
    expect($copy->getName())->toBe('copy');
});

it('allows source reads and blocks both row changes and DDL before execution', function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec('CREATE TABLE fixture (id INTEGER PRIMARY KEY, value TEXT)');
    $pdo->exec("INSERT INTO fixture VALUES (1, 'original')");
    $connection = new SQLiteConnection($pdo);
    MigrationRehearsalSafety::protectSource($connection);
    expect($connection->selectOne('SELECT value FROM fixture')->value)->toBe('original');
    foreach (["UPDATE fixture SET value = 'changed'", 'ALTER TABLE fixture ADD COLUMN accidental TEXT', 'DELETE FROM fixture'] as $query) {
        expect(fn () => $connection->statement($query))->toThrow(RuntimeException::class, 'Source connection write blocked');
    }
    expect($connection->selectOne('SELECT value FROM fixture')->value)->toBe('original')
        ->and(count($pdo->query('PRAGMA table_info(fixture)')->fetchAll()))->toBe(2);
});

it('blocks file-writing select statements through the source connection', function () {
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'));
    MigrationRehearsalSafety::protectSource($connection);
    expect(fn () => $connection->statement("SELECT 1 INTO OUTFILE '/unused'"))->toThrow(RuntimeException::class, 'Source connection write blocked');
});
