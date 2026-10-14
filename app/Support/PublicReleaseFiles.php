<?php

namespace App\Support;

use DirectoryIterator;
use RuntimeException;
use Throwable;

final class PublicReleaseFiles
{
    public function inspect(string $publicPath): array
    {
        $checks = array_fill_keys(['public_release_tree_inspected', 'no_public_repository_metadata',
            'no_public_backup_or_key_files', 'only_front_controller_scripts', 'public_symlink_targets_reviewed'], true);
        try {
            $root = realpath($publicPath);
            if ($root === false || ! is_dir($root)) {
                throw new RuntimeException('Public tree unavailable.');
            }
            $expectedStorage = realpath(dirname($root).'/storage/app/public');
            $pending = [[$root, '', 0]];
            $entries = 0;
            while ($pending !== []) {
                [$directory, $prefix, $depth] = array_pop($pending);
                if ($depth > 32) {
                    throw new RuntimeException('Public tree depth limit exceeded.');
                }
                foreach (new DirectoryIterator($directory) as $entry) {
                    if ($entry->isDot()) {
                        continue;
                    }
                    if (++$entries > 50000) {
                        throw new RuntimeException('Public tree entry limit exceeded.');
                    }
                    $name = $entry->getFilename();
                    $relative = $prefix.$name;
                    if (preg_match('/\A(?:\.env.*|\.git|\.svn|\.hg|\.local|\.codex|\.agents|\.aws|composer\.(?:json|lock)|package(?:-lock)?\.json|phpunit\.xml(?:\.dist)?)\z/i', $name)) {
                        $checks['no_public_repository_metadata'] = false;
                    }
                    if (preg_match('/\.(?:sql|sqlite[0-9]*|db|dump|zip|tar|gz|tgz|bz2|7z|rar|bundle|bak|backup|old|log|pem|key|p12|pfx)(?:\.|\z)/i', $name)) {
                        $checks['no_public_backup_or_key_files'] = false;
                    }
                    if ($relative !== 'index.php' && preg_match('/(?:\A|\.)(?:php[0-9]*|phtml|phar|cgi|pl|py|sh|shtml|inc)(?:\.|\z)/i', $name)) {
                        $checks['only_front_controller_scripts'] = false;
                    }
                    if ($entry->isLink()) {
                        $target = $entry->getRealPath();
                        // Only the conventional public-media link is followed; no arbitrary links or cycles.
                        if ($relative !== 'storage' || $expectedStorage === false || $target !== $expectedStorage || ! is_dir($target)) {
                            $checks['public_symlink_targets_reviewed'] = false;

                            continue;
                        }
                        $pending[] = [$target, 'storage/', $depth + 1];
                    } elseif ($entry->isDir()) {
                        $pending[] = [$entry->getPathname(), $relative.'/', $depth + 1];
                    } elseif (! $entry->isFile()) {
                        throw new RuntimeException('Unsupported public entry.');
                    }
                }
            }
        } catch (Throwable) {
            $checks['public_release_tree_inspected'] = false;
        }

        // Identifiers/statuses only: never expose filenames, contents or filesystem exception messages.
        return $checks;
    }
}
