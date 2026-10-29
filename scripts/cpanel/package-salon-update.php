<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit(1);
}
$root = dirname(__DIR__, 2);
$output = $argv[1] ?? '';
if (! is_dir($output) || iterator_count(new FilesystemIterator($output, FilesystemIterator::SKIP_DOTS)) !== 0) {
    throw new RuntimeException('Provide an existing empty private output directory.');
}
$git = static function (array $arguments) use ($root): string {
    $process = proc_open(array_merge(['git', '-c', 'safe.directory='.str_replace('\\', '/', $root), '-C', $root], $arguments),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (! is_resource($process)) {
        throw new RuntimeException('Cannot start Git.');
    }
    fclose($pipes[0]);
    $result = stream_get_contents($pipes[1]);
    $error = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('Source Git check failed.');
    }

    return $result;
};
if (trim($git(['status', '--porcelain'])) !== '') {
    throw new RuntimeException('Build only from a clean committed checkout.');
}
$commit = trim($git(['rev-parse', 'HEAD']));
$base = '3e28f89'; // Recorded source checkpoint of the uploaded cPanel installation.
$paths = array_filter(explode("\n", trim($git(['diff', '--name-only', '--diff-filter=AM', $base, $commit]))));
$application = [];
$migrations = [];
foreach ($paths as $path) {
    if (str_starts_with($path, 'app/') || str_starts_with($path, 'resources/')) {
        $application[$path] = $git(['show', $commit.':'.$path]);
    } elseif (preg_match('~^database/migrations/2026_10_29_00000[12]_~', $path)) {
        $migrations[$path] = $git(['show', $commit.':'.$path]);
    }
}
if (count($migrations) !== 2 || ! isset($application['app/Models/Salon.php'], $application['resources/views/storefront/salon.blade.php'])) {
    throw new RuntimeException('Expected feature files are missing.');
}
$installed = json_decode(file_get_contents($root.'/vendor/composer/installed.json'), true, 512, JSON_THROW_ON_ERROR);
if (($installed['dev'] ?? true) !== false) {
    throw new RuntimeException('Install locked production dependencies with --no-dev before packaging.');
}
$locked = json_decode($git(['show', $commit.':composer.lock']), true, 512, JSON_THROW_ON_ERROR);
$installedVersions = array_column($installed['packages'], 'version', 'name');
foreach ($locked['packages'] as $package) {
    if (($installedVersions[$package['name']] ?? null) !== $package['version']) {
        throw new RuntimeException('Production dependencies do not match the candidate lockfile.');
    }
}
$application['composer.lock'] = $git(['show', $commit.':composer.lock']);
// Ship the audited Mime patch and matching production Composer metadata, not a full vendor replacement.
foreach (['vendor/symfony/mime', 'vendor/composer'] as $directory) {
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($directory === 'vendor/composer' && ! in_array($file->getFilename(), [
            'autoload_real.php', 'autoload_static.php', 'autoload_psr4.php', 'autoload_namespaces.php',
            'autoload_files.php', 'autoload_classmap.php', 'ClassLoader.php', 'InstalledVersions.php',
            'installed.php', 'installed.json', 'platform_check.php', 'LICENSE',
        ], true)) {
            continue; // Never package Composer download archives or temporary files.
        }
        if ($file->isLink() || ! $file->isFile()) {
            throw new RuntimeException('Unexpected dependency patch entry.');
        }
        $path = str_replace('\\', '/', substr($file->getPathname(), strlen($root) + 1));
        $application[$path] = file_get_contents($file->getPathname());
    }
}
$application['vendor/autoload.php'] = file_get_contents($root.'/vendor/autoload.php');
$public = [];
$build = $root.'/public/build';
if (! is_file($build.'/manifest.json')) {
    throw new RuntimeException('Build assets in this clean checkout first.');
}
$viteManifest = json_decode(file_get_contents($build.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($build, FilesystemIterator::SKIP_DOTS)) as $file) {
    if ($file->isLink() || ! $file->isFile() || ! in_array(strtolower($file->getExtension()), ['json', 'css', 'js', 'woff', 'woff2', 'ttf', 'svg', 'png', 'jpg', 'webp'], true)) {
        throw new RuntimeException('Unexpected build artifact.');
    }
    $path = 'build/'.str_replace('\\', '/', substr($file->getPathname(), strlen($build) + 1));
    $public[$path] = file_get_contents($file->getPathname());
}
foreach ($viteManifest as $entry) {
    foreach (array_merge([$entry['file']], $entry['css'] ?? [], $entry['assets'] ?? []) as $asset) {
        if (! isset($public['build/'.$asset])) {
            throw new RuntimeException('Incomplete build assets.');
        }
    }
}
$hashes = static fn (array $files): array => array_map(fn ($contents) => hash('sha256', $contents), $files);
$manifest = ['update' => 'salon-features-2026-10-29', 'source_commit' => $commit, 'base_checkpoint' => $base,
    'migrations' => $hashes($migrations), 'application' => $hashes($application), 'public' => $hashes($public)];
$manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
$prepare = $migrations + [
    'ops/update-salon-features.php' => $git(['show', $commit.':deployment/cpanel/update-salon-features.php']),
    'ops/salon-features-manifest.json' => $manifestJson,
];
$archives = ['01-salon-update-prepare.zip' => $prepare, '02-salon-update-application.zip' => $application, '03-salon-update-public.zip' => $public];
$checksums = '';
foreach ($archives as $name => $files) {
    $zip = new ZipArchive;
    if ($zip->open($output.'/'.$name, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
        throw new RuntimeException('Cannot create update archive.');
    }
    foreach ($files as $path => $contents) {
        if (str_contains($path, '..') || str_starts_with($path, '/') || ! $zip->addFromString($path, $contents)) {
            throw new RuntimeException('Cannot add update file.');
        }
        $zip->setExternalAttributesName($path, ZipArchive::OPSYS_UNIX, 0100644 << 16);
    }
    if (! $zip->close()) {
        throw new RuntimeException('Cannot finish update archive.');
    }
    $check = new ZipArchive;
    if ($check->open($output.'/'.$name) !== true || $check->numFiles !== count($files)) {
        throw new RuntimeException('Cannot verify update archive.');
    }
    foreach ($files as $path => $contents) {
        if (! hash_equals(hash('sha256', $contents), hash('sha256', $check->getFromName($path)))) {
            throw new RuntimeException('Update archive content mismatch.');
        }
    }
    $check->close();
    $checksums .= hash_file('sha256', $output.'/'.$name).'  '.$name."\n";
    echo $name.' '.filesize($output.'/'.$name).' bytes, '.count($files)." files verified.\n";
}
file_put_contents($output.'/SHA256SUMS.txt', $checksums);
file_put_contents($output.'/salon-features-manifest.json', $manifestJson);
file_put_contents($output.'/READ-ME-FIRST.md', $git(['show', $commit.':deployment/cpanel/SALON-FEATURES-UPDATE.md']));
echo 'Source commit: '.$commit."\n";
