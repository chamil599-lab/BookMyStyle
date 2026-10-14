<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\PublicCatalogue;
use App\Support\MigrationRehearsalSafety;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$private = realpath($root.'/.local/migration-baseline');
$directory = getenv('BMS_IMPORTED_BROWSER_DIRECTORY') ?: $private.'/imported-browser-'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
$server = PHP_SAPI === 'cli-server';
$process = null;
$directoryValidated = false;
$exitCode = 0;
$source = null;
$before = null;
$fingerprint = static function ($connection, bool $business = false): array {
    $result = [];
    foreach ($connection->getSchemaBuilder()->getTableListing($connection->getDatabaseName(), false) as $table) {
        if ($business && in_array($table, ['sessions', 'cache', 'cache_locks', 'visits', 'site_visits'], true)) {
            continue;
        }
        $hashes = [];
        foreach ($connection->table($table)->cursor() as $row) {
            $values = (array) $row;
            if ($business && $table === 'users') {
                unset($values['remember_token']); // Native logout rotates this value.
            }
            $hashes[] = hash('sha256', serialize($values));
        }
        sort($hashes);
        $result[$table] = hash('sha256', implode('', $hashes));
    }
    ksort($result);

    return $result;
};
try {
    if ($private === false || realpath(dirname($directory)) !== $private
        || ! preg_match('/\Aimported-browser-[0-9]{8}_[0-9]{6}_[a-f0-9]{6}\z/', basename($directory))) {
        throw new RuntimeException('Unexpected private browser directory.');
    }
    $directoryValidated = true;
    if (! $server) {
        if (file_exists($directory)) {
            throw new RuntimeException('Use a new browser directory.');
        }
        mkdir($directory, 0700);
        mkdir($directory.'/views', 0700);
    }
    $runtime = $server ? json_decode(file_get_contents($directory.'/runtime.json'), true, flags: JSON_THROW_ON_ERROR) : null;
    if ($server && (! preg_match('/\Abms_index_check_[0-9]{8}_[0-9]{6}_[a-f0-9]{6}\z/', $runtime['connection']['database'])
        || $runtime['connection']['database'] === $runtime['source_database']
        || $runtime['connection']['driver'] !== 'mysql' || ! empty($runtime['connection']['url'])
        || ! in_array($runtime['connection']['host'], ['127.0.0.1', 'localhost', '::1'], true)
        || ! preg_match('/\Ahttp:\/\/127\.0\.0\.1:[0-9]+\z/', $runtime['url']))) {
        throw new RuntimeException('Unsafe browser copy configuration.');
    }
    $app = require $root.'/bootstrap/app.php';
    $app->afterBootstrapping(LoadConfiguration::class, function ($app) use ($directory, $runtime): void {
        $app['config']->set(['app.debug' => false, 'mail.default' => 'array', 'queue.default' => 'sync',
            'logging.default' => 'single', 'logging.channels.single.driver' => 'single',
            'logging.channels.single.path' => $directory.'/framework.log', 'view.compiled' => $directory.'/views',
            'services.google.client_id' => null, 'services.google.client_secret' => null,
            'operations.featured_cleanup_enabled' => false, 'operations.subscription_reminders_enabled' => false,
            'cache.default' => 'array', 'session.driver' => 'array']);
        if ($runtime !== null) {
            // Remove other configured connections: this HTTP worker must have no source database route.
            $app['config']->set(['app.url' => $runtime['url'], 'database.default' => 'imported_browser',
                'database.connections' => ['imported_browser' => $runtime['connection']],
                'session.driver' => 'database', 'session.connection' => 'imported_browser',
                'session.domain' => null, 'session.secure' => false, 'session.cookie' => 'bms_imported_browser',
                'cache.default' => 'database', 'cache.stores.database.connection' => 'imported_browser',
                'cache.stores.database.lock_connection' => 'imported_browser', 'cache.limiter' => 'database',
                'cache.prefix' => 'imported-browser:']);
        }
    });
    if ($server) {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $file = realpath($root.'/public'.$path);
        if ($file && str_starts_with($file, realpath($root.'/public').DIRECTORY_SEPARATOR) && is_file($file)
            && ! in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['php', 'phtml', 'phar'], true)) {
            return false;
        }
        $kernel = $app->make(Kernel::class);
        $request = Request::capture();
        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);

        return;
    }
    $app->make(ConsoleKernel::class)->bootstrap();
    $source = DB::connection();
    $settings = $source->getConfig();
    if (! $app->environment('local') || $settings['driver'] !== 'mysql' || ! empty($settings['url'])
        || ! in_array($settings['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
        throw new RuntimeException('Only local loopback MySQL is supported.');
    }
    MigrationRehearsalSafety::protectSource($source);
    $before = $fingerprint($source);
    $child = proc_open([PHP_BINARY, '-d', 'memory_limit=512M', __DIR__.'/verify-index-migrations.php', '--fresh',
        $argv[1] ?? 'mysqldump', $argv[2] ?? 'mysql'],
        [0 => ['pipe', 'r'], 1 => ['file', $directory.'/restore.json', 'w'], 2 => ['file', $directory.'/restore.stderr', 'w']], $pipes, $root);
    if (! is_resource($child)) {
        throw new RuntimeException('Unable to start fresh copy rehearsal.');
    }
    fclose($pipes[0]);
    if (proc_close($child) !== 0) {
        throw new RuntimeException('Fresh copy rehearsal failed.');
    }
    $restore = json_decode(file_get_contents($directory.'/restore.json'), true, flags: JSON_THROW_ON_ERROR);
    $artifact = $restore['artifact_directory'] ?? '';
    if (($restore['status'] ?? '') !== 'passed' || ! preg_match('/\Aindex-check-[0-9]{8}_[0-9]{6}_[a-f0-9]{6}\z/', $artifact)) {
        throw new RuntimeException('Unexpected restore artifact.');
    }
    $target = trim(file_get_contents($private.'/'.$artifact.'/target.txt'));
    if (! preg_match('/\Abms_index_check_[0-9]{8}_[0-9]{6}_[a-f0-9]{6}\z/', $target) || $target === $source->getDatabaseName()) {
        throw new RuntimeException('Unsafe browser copy target.');
    }
    $connection = MigrationRehearsalSafety::cloneConfiguration($settings, $target);
    config(['database.connections.imported_browser' => $connection]);
    $copy = DB::connection('imported_browser');
    DB::setDefaultConnection('imported_browser');
    if ($copy->getName() !== 'imported_browser' || $app->make('db.schema')->getConnection()->getDatabaseName() !== $target) {
        throw new RuntimeException('Copy routing mismatch.');
    }
    $accounts = [];
    $roleCounts = [];
    foreach (User::with('roles')->orderBy('id')->get() as $index => $user) {
        $kind = $user->hasRole('admin') ? 'admin' : ($user->hasRole('owner') ? 'owner' : ($user->hasRole('customer') ? 'customer' : 'other'));
        $roleCounts[$kind] = ($roleCounts[$kind] ?? 0) + 1;
        $password = bin2hex(random_bytes(24));
        // Direct update changes only the copy's credential; no model observer or notification runs.
        $copy->table('users')->where('id', $user->id)->update(['password' => Hash::make($password)]);
        $dashboard = ! $user->hasVerifiedEmail() ? '/verify-email' : ($kind === 'admin' ? '/filament/dashboard'
            : ($kind === 'owner' && $user->is_approved ? '/filament/owner-dashboard' : '/dashboard'));
        $pages = ['/profile'];
        if ($user->hasVerifiedEmail() && $kind === 'customer') {
            $pages = array_merge($pages, ['/dashboard/upcoming', '/dashboard/history', '/dashboard/favorites', '/dashboard/notifications']);
        } elseif ($user->hasVerifiedEmail() && ($kind === 'admin' || ($kind === 'owner' && $user->is_approved))) {
            $pages = array_merge($pages, ['/filament/salons', '/filament/user-subscriptions']);
            if ($kind === 'admin' || $user->hasActiveSubscription()) {
                $pages[] = '/filament/bookings';
            }
            if ($kind === 'admin') {
                $pages[] = '/filament/users';
            }
        }
        $accounts[] = ['label' => 'account-'.$index, 'kind' => $kind, 'email' => $user->email,
            'password' => $password, 'dashboard' => $dashboard, 'pages' => $pages];
    }
    $businessBefore = $fingerprint($copy, true);
    $fixture = ['accounts' => $accounts, 'salon_paths' => app(PublicCatalogue::class)->salons()->get()->map(fn ($salon) => '/salon/'.$salon->id)->all()];
    file_put_contents($directory.'/fixture.json', json_encode($fixture, JSON_THROW_ON_ERROR));
    $port = random_int(18000, 22000);
    $url = 'http://127.0.0.1:'.$port;
    file_put_contents($directory.'/runtime.json', json_encode(['connection' => $connection,
        'source_database' => $source->getDatabaseName(), 'url' => $url], JSON_THROW_ON_ERROR));
    $environment = getenv();
    $environment['BMS_IMPORTED_BROWSER_DIRECTORY'] = $directory;
    $process = proc_open([PHP_BINARY, '-d', 'memory_limit=512M', '-S', '127.0.0.1:'.$port, '-t', $root.'/public', __FILE__],
        [0 => ['pipe', 'r'], 1 => ['file', $directory.'/server.log', 'w'], 2 => ['file', $directory.'/server.log', 'a']], $pipes, $root, $environment);
    if (! is_resource($process)) {
        throw new RuntimeException('Unable to start isolated HTTP server.');
    }
    fclose($pipes[0]);
    $chrome = $argv[3] ?? 'C:/Program Files/Google/Chrome/Application/chrome.exe';
    if (! is_file($chrome)) {
        throw new RuntimeException('Chrome executable is required for browser verification.');
    }
    $browser = proc_open(['node', __DIR__.'/verify-imported-browser.mjs', $url, $directory, $chrome],
        [0 => ['pipe', 'r'], 1 => ['file', $directory.'/browser.log', 'w'], 2 => ['file', $directory.'/browser.log', 'a']], $pipes, $root);
    if (! is_resource($browser)) {
        throw new RuntimeException('Unable to start browser verification.');
    }
    fclose($pipes[0]);
    if (proc_close($browser) !== 0) {
        throw new RuntimeException('Imported browser verification failed.');
    }
    if ($fingerprint($copy, true) !== $businessBefore) {
        throw new RuntimeException('Unexpected copied business record changes.');
    }
    if ($fingerprint($source) !== $before) {
        throw new RuntimeException('Imported source record fingerprints changed.');
    }
    $browserReport = json_decode(file_get_contents($directory.'/browser-summary.json'), true, flags: JSON_THROW_ON_ERROR);
    $report = ['status' => 'passed', 'artifact_directory' => basename($directory), 'role_counts' => $roleCounts,
        'browser_checks' => count($browserReport['checks']), 'accounts_logged_in' => count($accounts),
        'screenshots' => $browserReport['screenshots'], 'uncaught_javascript_errors' => $browserReport['uncaught_javascript_errors'],
        'source_unchanged' => true, 'copy_business_records_preserved' => true,
        'scope' => 'Fresh isolated MySQL copy; temporary copy-only passwords; real Chrome form login; mail suppressed; external browser requests blocked'];
    file_put_contents($directory.'/summary.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    if ($directoryValidated && is_dir($directory)) {
        file_put_contents($directory.'/failure.txt', $exception->getMessage());
        if (! $server && $source !== null && $before !== null) {
            try {
                file_put_contents($directory.'/source-integrity.json', json_encode(['source_unchanged' => $fingerprint($source) === $before], JSON_THROW_ON_ERROR));
            } catch (Throwable) {
                // Preserve the original failure privately; no raw database error is printed.
            }
        }
    }
    fwrite(STDERR, "Imported-browser verification failed; inspect private artifacts.\n");
    $exitCode = 1;
} finally {
    if (is_resource($process)) {
        proc_terminate($process);
        proc_close($process);
    }
    if (! $server && $directoryValidated && is_file($directory.'/runtime.json')) {
        unlink($directory.'/runtime.json'); // The worker is stopped; do not retain database credentials.
    }
}
exit($exitCode);
