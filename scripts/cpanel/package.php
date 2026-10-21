<?php

declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
ini_set('memory_limit', '512M');
$root = dirname(__DIR__, 2);
$paths = json_decode(file_get_contents($root.'/.local/cpanel-current.json'), true, 512, JSON_THROW_ON_ERROR);
foreach (['app', 'web', 'output'] as $key) {
    $resolved = realpath($paths[$key]);
    if (! $resolved || ! str_starts_with($resolved, realpath($root.'/.local').DIRECTORY_SEPARATOR.'cpanel-')) {
        throw new RuntimeException('Unexpected staging path.');
    }
}
// Bind the public path for both HTTP requests and Artisan (assets/media links).
$bootstrap = $paths['app'].'/bootstrap/app.php';
$source = file_get_contents($bootstrap);
if (! str_contains($source, 'return Application::configure(')) {
    throw new RuntimeException('Unexpected bootstrap format.');
}
$source = str_replace('return Application::configure(', '$app = Application::configure(', $source);
$source .= "\n\$app->usePublicPath('/home/limachlk/public_html/bookmystyle.limach.lk');\nreturn \$app;\n";
file_put_contents($bootstrap, $source);
foreach (['app' => 'bookmystyle-application.zip', 'web' => 'bookmystyle-public.zip'] as $tree => $filename) {
    $zip = new ZipArchive;
    $destination = $paths['output'].'/'.$filename;
    if ($zip->open($destination, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
        throw new RuntimeException('Cannot create archive.');
    }
    $base = $paths[$tree];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $file) {
        $relative = str_replace('\\', '/', substr($file->getPathname(), strlen($base) + 1));
        if ($file->isLink()) {
            throw new RuntimeException('Unexpected link in release.');
        }
        if ($relative === '.env' || preg_match('~^(storage/.+|bootstrap/cache/.+)~', $relative) && $file->isFile()) {
            continue;
        }
        if ($file->isDir()) {
            $zip->addEmptyDir($relative);
            $zip->setExternalAttributesName($relative, ZipArchive::OPSYS_UNIX, 040755 << 16);
        } else {
            $ok = DIRECTORY_SEPARATOR === '\\' && strlen($file->getPathname()) >= 240
                ? $zip->addFromString($relative, file_get_contents($file->getPathname()))
                : $zip->addFile($file->getPathname(), $relative);
            if (! $ok) {
                throw new RuntimeException('Cannot add release file.');
            }
            $zip->setExternalAttributesName($relative, ZipArchive::OPSYS_UNIX, 0100644 << 16);
        }
    }
    if (! $zip->close()) {
        throw new RuntimeException('Cannot finish archive.');
    }
    $check = new ZipArchive;
    if ($check->open($destination) !== true) {
        throw new RuntimeException('Cannot reopen archive.');
    }
    $required = $tree === 'app'
        ? ['artisan', 'composer.lock', '.env.example', 'vendor/autoload.php', 'ops/setup.php', 'ops/admin.json.example', 'app/Services/FreshInstallationService.php']
        : ['index.php', '.htaccess', 'build/manifest.json', 'images/brand-mark-blue.svg', 'css/filament/filament/app.css'];
    foreach ($required as $file) {
        if ($check->locateName($file) === false) {
            throw new RuntimeException('Required archive file missing: '.$file);
        }
    }
    for ($i = 0; $i < $check->numFiles; $i++) {
        $name = $check->getNameIndex($i);
        if (preg_match('~^(\.env$|\.git/|\.local/|node_modules/|vendor/(phpunit|pestphp)/)|^(bootstrap/cache|storage)/.+\.php$~', $name)) {
            throw new RuntimeException('Private/development runtime file in archive.');
        }
        if ($tree === 'web' && preg_match('~\.(php[0-9]*|phtml|phar)(?:[./]|$)~i', $name) && $name !== 'index.php') {
            throw new RuntimeException('Unexpected public server script.');
        }
    }
    $check->close();
    echo $filename.' '.filesize($destination).' bytes SHA256 '.hash_file('sha256', $destination).PHP_EOL;
}
