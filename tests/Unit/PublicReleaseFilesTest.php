<?php

use App\Support\PublicReleaseFiles;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->releaseFilesDirectory = dirname(__DIR__, 2).'/.local/migration-baseline/release-files-test-'.bin2hex(random_bytes(6));
    mkdir($this->releaseFilesDirectory.'/public', 0700, true);
});

afterEach(function () {
    $target = realpath($this->releaseFilesDirectory);
    $private = realpath(dirname(__DIR__, 2).'/.local/migration-baseline');
    if ($target !== false && dirname($target) === $private && str_starts_with(basename($target), 'release-files-test-')) {
        (new Filesystem)->deleteDirectory($target);
    }
});

it('permits normal release assets and only the root front controller', function () {
    mkdir($this->releaseFilesDirectory.'/public/build');
    foreach (['index.php', '.htaccess', 'build/manifest.json', 'build/app.js', 'build/app.css', 'placeholder.svg'] as $file) {
        file_put_contents($this->releaseFilesDirectory.'/public/'.$file, 'fixture');
    }
    expect(array_unique(array_values((new PublicReleaseFiles)->inspect($this->releaseFilesDirectory.'/public'))))->toBe([true]);
});

it('rejects nested unsafe artifacts without returning their names or contents', function ($file, $check) {
    mkdir($this->releaseFilesDirectory.'/public/nested');
    file_put_contents($this->releaseFilesDirectory.'/public/nested/'.$file, 'private-fixture-contents');
    $report = (new PublicReleaseFiles)->inspect($this->releaseFilesDirectory.'/public');
    expect($report[$check])->toBeFalse()
        ->and(json_encode($report))->not->toContain($file, 'private-fixture-contents', $this->releaseFilesDirectory);
})->with([
    ['.env.production', 'no_public_repository_metadata'],
    ['composer.lock', 'no_public_repository_metadata'],
    ['customers.sql.gz', 'no_public_backup_or_key_files'],
    ['archive.bundle', 'no_public_backup_or_key_files'],
    ['signing.pem', 'no_public_backup_or_key_files'],
    ['index.php', 'only_front_controller_scripts'],
    ['picture.PHP.jpg', 'only_front_controller_scripts'],
]);

it('fails a missing tree and does not disclose its path', function () {
    $report = (new PublicReleaseFiles)->inspect($this->releaseFilesDirectory.'/missing');
    expect($report['public_release_tree_inspected'])->toBeFalse()
        ->and(json_encode($report))->not->toContain($this->releaseFilesDirectory);
});

it('detects repository directories even when they are empty', function () {
    mkdir($this->releaseFilesDirectory.'/public/.git');
    expect((new PublicReleaseFiles)->inspect($this->releaseFilesDirectory.'/public')['no_public_repository_metadata'])->toBeFalse();
});

it('inspects the conventional storage link and rejects other links without following them', function () {
    mkdir($this->releaseFilesDirectory.'/storage/app/public', 0700, true);
    file_put_contents($this->releaseFilesDirectory.'/storage/app/public/private.sql', 'fixture');
    set_error_handler(static fn () => true);
    try {
        $created = symlink($this->releaseFilesDirectory.'/storage/app/public', $this->releaseFilesDirectory.'/public/storage');
    } finally {
        restore_error_handler();
    }
    if (! $created) {
        $this->markTestSkipped('Directory symlink creation is unavailable on this host.');
    }
    $report = (new PublicReleaseFiles)->inspect($this->releaseFilesDirectory.'/public');
    expect($report['public_symlink_targets_reviewed'])->toBeTrue()->and($report['no_public_backup_or_key_files'])->toBeFalse();
    symlink($this->releaseFilesDirectory.'/storage/app/public', $this->releaseFilesDirectory.'/public/other');
    expect((new PublicReleaseFiles)->inspect($this->releaseFilesDirectory.'/public')['public_symlink_targets_reviewed'])->toBeFalse();
});
