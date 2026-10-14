<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\PublicCatalogue;
use App\Support\MigrationRehearsalSafety;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportRedirects\SupportRedirects;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$private = realpath($root.'/.local/migration-baseline');
$directory = null;
$exitCode = 0;
try {
    if ($private === false) {
        throw new RuntimeException('Private migration directory missing.');
    }
    $directory = $private.'/imported-pages-'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
    mkdir($directory, 0700);
    mkdir($directory.'/views', 0700);
    $app = require $root.'/bootstrap/app.php';
    $app->afterBootstrapping(LoadConfiguration::class, function ($app) use ($directory): void {
        $app['config']->set(['mail.default' => 'array', 'cache.default' => 'array', 'session.driver' => 'array',
            'session.domain' => null, 'session.secure' => false, 'queue.default' => 'sync', 'app.debug' => false,
            'app.url' => 'http://127.0.0.1', 'view.compiled' => $directory.'/views',
            'logging.default' => 'single', 'logging.channels.single.driver' => 'single',
            'logging.channels.single.path' => $directory.'/framework.log', 'services.google.client_id' => null,
            'operations.featured_cleanup_enabled' => false, 'operations.subscription_reminders_enabled' => false]);
    });
    $app->make(ConsoleKernel::class)->bootstrap();
    $source = DB::connection();
    $settings = $source->getConfig();
    if (! $app->environment('local') || $settings['driver'] !== 'mysql' || ! empty($settings['url'])
        || ! in_array($settings['host'], ['127.0.0.1', 'localhost', '::1'], true)) {
        throw new RuntimeException('Only the local loopback MySQL workspace is supported.');
    }
    MigrationRehearsalSafety::protectSource($source);
    $fingerprint = static function ($connection): array {
        $result = [];
        foreach ($connection->getSchemaBuilder()->getTableListing($connection->getDatabaseName(), false) as $table) {
            $hashes = [];
            foreach ($connection->table($table)->cursor() as $row) {
                $hashes[] = hash('sha256', serialize((array) $row));
            }
            sort($hashes);
            $result[$table] = hash('sha256', implode('', $hashes));
        }
        ksort($result);

        return $result;
    };
    $before = $fingerprint($source);
    $process = proc_open([PHP_BINARY, '-d', 'memory_limit=512M', $root.'/scripts/migration/verify-index-migrations.php',
        '--fresh', $argv[1] ?? 'mysqldump', $argv[2] ?? 'mysql'],
        [0 => ['pipe', 'r'], 1 => ['file', $directory.'/restore.json', 'w'], 2 => ['file', $directory.'/restore.stderr', 'w']], $pipes, $root);
    if (! is_resource($process)) {
        throw new RuntimeException('Could not start isolated restore.');
    }
    fclose($pipes[0]);
    if (proc_close($process) !== 0) {
        throw new RuntimeException('Fresh isolated restore/rehearsal failed.');
    }
    $restore = json_decode(file_get_contents($directory.'/restore.json'), true, flags: JSON_THROW_ON_ERROR);
    $artifact = $restore['artifact_directory'] ?? '';
    if (($restore['status'] ?? '') !== 'passed' || ! preg_match('/\Aindex-check-[0-9]{8}_[0-9]{6}_[a-f0-9]{6}\z/', $artifact)) {
        throw new RuntimeException('Unexpected restore artifact.');
    }
    $target = trim(file_get_contents($private.'/'.$artifact.'/target.txt'));
    if (! preg_match('/\Abms_index_check_[0-9]{8}_[0-9]{6}_[a-f0-9]{6}\z/', $target) || $target === $source->getDatabaseName()) {
        throw new RuntimeException('Unsafe copy target.');
    }
    config(['database.connections.imported_pages' => MigrationRehearsalSafety::cloneConfiguration($settings, $target)]);
    $copy = DB::connection('imported_pages');
    if ($copy->getName() !== 'imported_pages' || $copy->getDatabaseName() !== $target) {
        throw new RuntimeException('Copy routing mismatch.');
    }
    DB::setDefaultConnection('imported_pages');
    if ($app->make('db.schema')->getConnection()->getDatabaseName() !== $target) {
        throw new RuntimeException('Schema resolver does not point to the copy.');
    }
    $kernel = $app->make(Kernel::class);
    $checks = [];
    $gated = [];
    $probe = function (string $label, string $path, ?User $actor, array $expected, ?string $redirect = null) use ($app, $kernel, $directory, &$checks): int {
        // Each probe represents a fresh PHP request, including after a denied Livewire mount.
        app('livewire')->flushState();
        SupportRedirects::$redirectorCacheStack = [];
        $redirector = new Redirector($app['url']);
        $redirector->setSession($app['session.store']);
        $app->instance('redirect', $redirector);
        $app['session']->flush();
        $app['session']->regenerate();
        Auth::forgetGuards();
        Auth::shouldUse('web');
        if ($actor !== null) {
            Auth::guard('web')->setUser($actor);
        }
        $request = Request::create('http://127.0.0.1'.$path, 'GET', server: ['HTTP_ACCEPT' => 'text/html']);
        $response = $kernel->handle($request);
        $status = $response->getStatusCode();
        file_put_contents($directory.'/'.$label.'.html', $response->getContent());
        $kernel->terminate($request, $response);
        $destination = parse_url($response->headers->get('Location', ''), PHP_URL_PATH);
        $checks[$label] = ['status' => $status, 'expected' => $expected,
            'pass' => in_array($status, $expected, true) && ($redirect === null || $destination === $redirect)];
        file_put_contents($directory.'/checks.json', json_encode($checks, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        if (! $checks[$label]['pass']) {
            throw new RuntimeException('Imported-page response failed for '.$label);
        }

        return $status;
    };
    foreach (['/', '/salons', '/trending', '/popular', '/services', '/deals', '/about', '/privacy-policy', '/terms-and-condition', '/sitemap.xml', '/robots.txt', '/login'] as $index => $path) {
        $probe('guest-'.$index, $path, null, [200]);
    }
    $publicSalons = app(PublicCatalogue::class)->salons()->get();
    foreach ($publicSalons as $index => $salon) {
        $probe('salon-'.$index, '/salon/'.$salon->id, null, [200]);
    }
    $roles = [];
    $hashTypes = [];
    foreach (User::with('roles')->orderBy('id')->get() as $index => $user) {
        $kind = $user->hasRole('admin') ? 'admin' : ($user->hasRole('owner') ? 'owner' : ($user->hasRole('customer') ? 'customer' : 'other'));
        $roles[$kind] = ($roles[$kind] ?? 0) + 1;
        $password = $user->getRawOriginal('password');
        $hash = $password ? password_get_info($password)['algoName'] : 'passwordless';
        $hashTypes[$hash] = ($hashTypes[$hash] ?? 0) + 1;
        $probe('account-'.$index.'-profile', '/profile', $user, [200]);
        $dashboardRedirect = ! $user->hasVerifiedEmail() ? '/verify-email' : ($kind === 'admin' ? '/filament/dashboard'
            : ($kind === 'owner' && $user->is_approved ? '/filament/owner-dashboard' : null));
        $probe('account-'.$index.'-dashboard', '/dashboard', $user, $dashboardRedirect === null ? [200] : [302], $dashboardRedirect);
        if ($kind === 'customer') {
            foreach (['upcoming', 'history', 'favorites', 'notifications'] as $page) {
                $probe('account-'.$index.'-'.$page, '/dashboard/'.$page, $user, $user->hasVerifiedEmail() ? [200] : [302], $user->hasVerifiedEmail() ? null : '/verify-email');
            }
        } elseif ($kind === 'admin' || $kind === 'owner') {
            $allowed = $user->hasVerifiedEmail() && ($kind === 'admin' || $user->is_approved);
            $paths = $kind === 'admin' ? ['/filament/dashboard', '/filament/users', '/filament/bookings', '/filament/salons', '/filament/user-subscriptions']
                : ['/filament/owner-dashboard', '/filament/bookings', '/filament/salons', '/filament/user-subscriptions'];
            foreach ($paths as $number => $path) {
                $expected = $allowed ? [200] : [302, 403];
                if ($allowed && $kind === 'owner' && $path === '/filament/bookings' && ! $user->hasActiveSubscription()) {
                    $expected = [403];
                }
                $status = $probe('account-'.$index.'-panel-'.$number, $path, $user, $expected);
                if ($status !== 200) {
                    $gated[$kind] = ($gated[$kind] ?? 0) + 1;
                }
            }
        }
    }
    if ($fingerprint($source) !== $before) {
        throw new RuntimeException('Source records changed during imported-page verification.');
    }
    $report = ['status' => 'passed', 'artifact_directory' => basename($directory), 'page_checks' => count($checks),
        'public_salons_checked' => $publicSalons->count(), 'role_counts' => $roles, 'password_hash_types' => $hashTypes,
        'expected_access_gates' => $gated, 'source_unchanged' => true, 'checks' => $checks,
        'scope' => 'Isolated MySQL copy; native GET rendering with injected authentication; no real password/OAuth login, browser execution or external mail'];
    file_put_contents($directory.'/summary.json', json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)."\n");
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    if ($directory !== null) {
        file_put_contents($directory.'/failure.txt', $exception->getMessage());
    }
    fwrite(STDERR, "Imported-page verification failed; inspect private artifacts.\n");
    $exitCode = 1;
}
exit($exitCode);
