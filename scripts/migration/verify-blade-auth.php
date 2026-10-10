<?php

// Real HTTP/Livewire smoke check. All records, sessions, cache and mail are isolated.
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;

$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
$directory = getenv('BMS_AUTH_SMOKE_DIRECTORY') ?: $root.'/.local/migration-baseline/auth-smoke-'.date('Ymd_His').'_'.bin2hex(random_bytes(3));
$parent = realpath($root.'/.local/migration-baseline');
if (realpath(dirname($directory)) !== $parent || ! preg_match('/^auth-smoke-[0-9]{8}_[0-9]{6}_[a-f0-9]{6}$/', basename($directory))) {
    throw new RuntimeException('The smoke database must be in the private migration directory.');
}
if (! is_dir($directory)) {
    mkdir($directory, 0700);
}
$database = $directory.'/database.sqlite';
if (! file_exists($database)) {
    touch($database);
}
$port = (int) (getenv('BMS_AUTH_SMOKE_PORT') ?: random_int(18000, 22000));
$url = 'http://127.0.0.1:'.$port;
$app = require $root.'/bootstrap/app.php';
$app->afterBootstrapping(LoadConfiguration::class, function ($app) use ($database, $url) {
    $app['config']->set(['app.env' => 'local', 'app.url' => $url, 'app.debug' => false,
        'database.default' => 'sqlite', 'database.connections.sqlite.database' => $database,
        'database.connections.sqlite.foreign_key_constraints' => true,
        'session.driver' => 'database', 'session.connection' => 'sqlite', 'session.secure' => false,
        'session.domain' => null, 'cache.default' => 'database', 'cache.stores.database.connection' => 'sqlite',
        'mail.default' => 'array', 'queue.default' => 'sync', 'services.google.client_id' => null]);
});

if (PHP_SAPI === 'cli-server') {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $file = realpath($root.'/public'.$path);
    if ($file && str_starts_with($file, realpath($root.'/public').DIRECTORY_SEPARATOR) && is_file($file) && pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
        return false;
    }
    $app->handleRequest(Illuminate\Http\Request::capture());

    return;
}

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== $database) {
    throw new RuntimeException('The application did not select the isolated database.');
}
Artisan::call('migrate', ['--force' => true]);
Role::findOrCreate('customer', 'web');
$password = bin2hex(random_bytes(16));
$user = User::factory()->create(['email' => 'smoke@example.test', 'password' => $password, 'is_approved' => true]);
$user->assignRole('customer');
$log = $directory.'/server.log';
$environment = array_merge(getenv(), ['BMS_AUTH_SMOKE_DIRECTORY' => $directory, 'BMS_AUTH_SMOKE_PORT' => (string) $port]);
$process = proc_open([PHP_BINARY, '-S', '127.0.0.1:'.$port, '-t', $root.'/public', __FILE__],
    [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $root, $environment);
if (! is_resource($process)) {
    throw new RuntimeException('Unable to start the isolated HTTP server.');
}
fclose($pipes[0]);
$client = new Client(['base_uri' => $url, 'cookies' => new CookieJar, 'http_errors' => false, 'allow_redirects' => false, 'timeout' => 10]);
$checks = [];
$expect = function (bool $condition, string $label) use (&$checks) {
    if (! $condition) {
        throw new RuntimeException('Smoke check failed: '.$label);
    }
    $checks[$label] = true;
};
try {
    $deadline = microtime(true) + 15;
    do {
        try {
            $response = $client->get('/login');
            break;
        } catch (GuzzleHttp\Exception\ConnectException $exception) {
            if (microtime(true) > $deadline) {
                throw $exception;
            }
            usleep(100000);
        }
    } while (true);
    $html = (string) $response->getBody();
    file_put_contents($directory.'/login.html', $html);
    $expect($response->getStatusCode() === 200 && str_contains($html, 'Sign in to BookMyStyle'), 'login_html');
    $expect(str_contains($response->getHeaderLine('Set-Cookie'), 'httponly') && str_contains($response->getHeaderLine('Set-Cookie'), 'samesite=lax'), 'http_only_same_site_cookie');
    $expect(str_contains($html, '/livewire/livewire'), 'livewire_script');
    preg_match('/name="csrf-token" content="([^"]+)"/', $html, $csrf);
    preg_match('/wire:snapshot="([^"]+)"/', $html, $snapshot);
    if (! $csrf || ! $snapshot) {
        throw new RuntimeException('Unable to find Livewire request metadata.');
    }
    $expect($client->post('/logout')->getStatusCode() === 419, 'csrf_rejects_missing_token');
    $request = ['_token' => $csrf[1], 'components' => [['snapshot' => html_entity_decode($snapshot[1], ENT_QUOTES),
        'updates' => ['form.email' => $user->email, 'form.password' => $password], 'calls' => [['path' => '', 'method' => 'login', 'params' => []]]]]];
    $response = $client->post('/livewire/update', ['json' => $request, 'headers' => ['X-Livewire' => 'true', 'Accept' => 'application/json']]);
    $payload = json_decode((string) $response->getBody(), true);
    $expect($response->getStatusCode() === 200 && str_ends_with($payload['components'][0]['effects']['redirect'] ?? '', '/dashboard'), 'livewire_session_login');
    $response = $client->get('/dashboard');
    $expect($response->getStatusCode() === 200 && str_contains((string) $response->getBody(), 'Your account'), 'authenticated_dashboard');
    $response = $client->get('/profile');
    $html = (string) $response->getBody();
    $expect($response->getStatusCode() === 200 && str_contains($html, 'Profile Information'), 'authenticated_profile');
    preg_match('/name="csrf-token" content="([^"]+)"/', $html, $csrf);
    $response = $client->post('/logout', ['form_params' => ['_token' => $csrf[1]]]);
    $expect($response->getStatusCode() === 302, 'csrf_logout');
    $response = $client->get('/dashboard');
    $expect($response->getStatusCode() === 302 && str_ends_with($response->getHeaderLine('Location'), '/login'), 'logout_blocks_dashboard');
    foreach (['/register', '/forgot-password', '/reset-password/synthetic-token'] as $path) {
        $expect($client->get($path)->getStatusCode() === 200, $path);
    }
    $summary = ['scope' => 'Synthetic SQLite database; real local HTTP; mail suppressed', 'checks' => $checks];
    file_put_contents($directory.'/summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    echo json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
} catch (Throwable $exception) {
    $failure = $exception;
} finally {
    proc_terminate($process);
    proc_close($process);
}

if (isset($failure)) {
    fwrite(STDERR, $failure->getMessage().PHP_EOL);
    exit(1);
}
