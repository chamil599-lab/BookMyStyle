<?php

namespace App\Support;

use Illuminate\Contracts\Config\Repository;

final class DeploymentReadiness
{
    public function report(Repository $config, string $publicPath): array
    {
        $checks = [];
        $check = static function (string $id, bool $passed) use (&$checks): void {
            $checks[$id] = $passed ? 'pass' : 'fail';
        };
        $check('production_environment', $config->get('app.env') === 'production');
        $check('debug_disabled', $config->get('app.debug') === false);
        $ttl = $config->get('sanctum.expiration');
        $check('finite_api_token_lifetime', is_int($ttl) && $ttl >= 1 && $ttl <= 43200);
        $check('https_application_url', $this->secureUrl($config->get('app.url')));
        $key = (string) $config->get('app.key');
        $decoded = str_starts_with($key, 'base64:') ? base64_decode(substr($key, 7), true) : $key;
        $check('valid_encryption_key', is_string($decoded) && strlen($decoded) === 32 && $config->get('app.cipher') === 'AES-256-CBC');
        $check('secure_session_cookie', $config->get('session.secure') === true);
        $check('http_only_session_cookie', $config->get('session.http_only') === true);
        $check('restricted_same_site_cookie', in_array($config->get('session.same_site'), ['lax', 'strict'], true));
        $check('persistent_session', in_array($config->get('session.driver'), ['database', 'redis', 'file'], true));
        $check('persistent_cache', in_array($config->get('cache.default'), ['database', 'redis', 'file', 'memcached'], true));
        $check('configured_mail_transport', in_array($config->get('mail.default'), ['smtp', 'sendmail', 'ses', 'postmark', 'resend', 'mailgun'], true));
        $check('mysql_database_selected', $config->get('database.connections.'.$config->get('database.default').'.driver') === 'mysql');
        $origins = $config->get('cors.allowed_origins', []);
        $check('restricted_cors_origins', is_array($origins) && $this->all($origins, fn ($origin) => $this->secureUrl($origin) && parse_url($origin, PHP_URL_PATH) === null));
        $check('no_cors_origin_patterns', $config->get('cors.allowed_origins_patterns', []) === []);
        $proxies = $config->get('app.trusted_proxies', []);
        $check('explicit_trusted_proxies', is_array($proxies) && $this->all($proxies, function ($proxy): bool {
            if (! is_string($proxy)) {
                return false;
            }
            $parts = explode('/', $proxy);
            $ip = filter_var($parts[0], FILTER_VALIDATE_IP);

            return $ip !== false && count($parts) <= 2 && (count($parts) === 1 || (ctype_digit($parts[1]) && (int) $parts[1] > 0 && (int) $parts[1] <= (str_contains($ip, ':') ? 128 : 32)));
        }));
        $check('no_vite_development_marker', ! file_exists($publicPath.'/hot'));
        $check('no_react_index_override', ! file_exists($publicPath.'/index.html'));
        $check('no_public_project_secrets', ! file_exists($publicPath.'/.env') && ! file_exists($publicPath.'/composer.json') && ! file_exists($publicPath.'/composer.lock'));
        foreach ((new PublicReleaseFiles)->inspect($publicPath) as $id => $passed) {
            $check($id, $passed);
        }
        $manifest = is_file($publicPath.'/build/manifest.json') ? json_decode(file_get_contents($publicPath.'/build/manifest.json'), true) : null;
        $assetsValid = is_array($manifest) && isset($manifest['resources/css/app.css'], $manifest['resources/js/app.js']);
        foreach (is_array($manifest) ? $manifest : [] as $entry) {
            foreach (array_merge([$entry['file'] ?? ''], $entry['css'] ?? [], $entry['assets'] ?? []) as $asset) {
                $path = realpath($publicPath.'/build/'.$asset);
                $build = realpath($publicPath.'/build');
                $assetsValid = $assetsValid && $asset !== '' && $path !== false && $build !== false && str_starts_with($path, $build.DIRECTORY_SEPARATOR) && is_file($path);
            }
        }
        $check('root_build_assets_present', $assetsValid);
        $check('filament_assets_present', is_file($publicPath.'/css/filament/filament/app.css') && is_file($publicPath.'/js/filament/filament/app.js'));

        return [
            'scope' => 'Configuration and local files only; no database queries, mail, network requests or deployment',
            'automated_checks' => $checks,
            'automated_pass' => ! in_array('fail', $checks, true),
            'release_ready' => false,
            'manual_gates' => array_fill_keys([
                'actual_document_root_and_https', 'upload_execution_disabled_on_host',
                'host_php_extensions_and_optimized_autoloader', 'backup_restore_and_rollback',
                'isolated_pending_migration_validation', 'mail_delivery_and_password_reset',
                'google_oauth_real_credentials', 'payment_approval_workflow',
                'queue_worker_and_cron_operations', 'production_browser_and_load_checks',
            ], 'unverified'),
        ];
    }

    private function secureUrl(mixed $value): bool
    {
        if (! is_string($value) || ! filter_var($value, FILTER_VALIDATE_URL)) {
            return false;
        }
        $url = parse_url($value);
        $host = strtolower($url['host'] ?? '');

        return ($url['scheme'] ?? '') === 'https' && ! isset($url['user']) && ! isset($url['pass'])
            && ! isset($url['query']) && ! isset($url['fragment'])
            && $host !== 'localhost' && str_contains($host, '.')
            && ! str_ends_with($host, '.test') && ! str_ends_with($host, '.local')
            && filter_var($host, FILTER_VALIDATE_IP) === false;
    }

    private function all(array $values, callable $predicate): bool
    {
        foreach ($values as $value) {
            if (! $predicate($value)) {
                return false;
            }
        }

        return true;
    }
}
