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
Role::findOrCreate('owner', 'web');
$ownerPassword = bin2hex(random_bytes(16));
$owner = User::factory()->create(['email' => 'owner-smoke@example.test', 'password' => $ownerPassword, 'is_approved' => true]);
$owner->assignRole('owner');
Role::findOrCreate('admin', 'web');
$adminPassword = bin2hex(random_bytes(16));
$admin = User::factory()->create(['email' => 'admin-smoke@example.test', 'password' => $adminPassword]);
$admin->assignRole('admin');
$salon = \App\Models\Salon::create(['name' => 'Amber Studio', 'owner_name' => 'Fixture Owner', 'location' => 'Kandy',
    'description' => 'Your new style starts here.', 'created_by' => $owner->id, 'updated_by' => $owner->id,
    'show_price' => true, 'number_of_staff' => 1, 'open_time' => '09:00', 'close_time' => '17:00']);
$department = \App\Models\Department::create(['name' => 'Hair', 'slug' => 'hair', 'is_active' => true]);
$category = \App\Models\Category::create(['name' => 'Cuts', 'department_id' => $department->id]);
$service = \App\Models\Service::create(['title' => 'Signature cut', 'slug' => 'signature-cut', 'description' => 'A fresh cut.',
    'created_by' => $owner->id, 'updated_by' => $owner->id, 'department_id' => $department->id, 'category_id' => $category->id,
    'price' => 2500, 'duration' => 60, 'status' => 'published']);
$package = \App\Models\SubscriptionPackage::create(['name' => 'Fixture', 'slug' => 'fixture', 'price' => 100, 'duration_days' => 30, 'max_salons' => 5, 'max_staff' => 5, 'max_services' => 5, 'analytics' => true]);
\App\Models\UserSubscription::create(['user_id' => $owner->id, 'package_id' => $package->id, 'amount_paid' => 100, 'status' => 'active', 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonth()]);
\App\Models\ServicePage::create(['title' => 'Signature beauty', 'status' => 'published', 'description' => 'Care for your style.', 'price' => 2500, 'duration' => 60]);
\App\Models\DealsPage::create(['title' => 'A little extra', 'status' => 1, 'original_price' => 2500, 'discounted_price' => 2000, 'salon' => $salon->name]);
$bookingData = ['user_id' => $user->id, 'salon_id' => $salon->id, 'service_id' => $service->id,
    'name' => $user->name, 'email' => $user->email, 'phone' => '0700000000', 'date' => today()->addDays(2)->format('Y-m-d'), 'time' => '10:00', 'status' => 'approved'];
$customerBooking = \App\Models\Booking::create($bookingData);
\App\Models\Booking::create(array_merge($bookingData, ['date' => today()->subDay()->format('Y-m-d'), 'status' => 'completed']));
$otherCustomer = User::factory()->create();
$foreignBooking = \App\Models\Booking::create(array_merge($bookingData, ['user_id' => $otherCustomer->id]));
\App\Models\FavoriteSalon::create(['user_id' => $user->id, 'salon_id' => $salon->id]);
$user->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'fixture',
    'data' => ['title' => 'Booking approved', 'message' => 'Your appointment at Amber Studio was approved.']]);
$fixtureFile = $directory.'/customer-fixture.json';
file_put_contents($fixtureFile, json_encode(['email' => $user->email, 'password' => $password,
    'bookingId' => $customerBooking->id, 'owner' => ['email' => $owner->email, 'password' => $ownerPassword],
    'admin' => ['email' => $admin->email, 'password' => $adminPassword]], JSON_THROW_ON_ERROR));
$log = $directory.'/server.log';
$environment = array_merge(getenv(), ['BMS_AUTH_SMOKE_DIRECTORY' => $directory, 'BMS_AUTH_SMOKE_PORT' => (string) $port]);
$process = proc_open([PHP_BINARY, '-d', 'memory_limit=512M', '-S', '127.0.0.1:'.$port, '-t', $root.'/public', __FILE__],
    [0 => ['pipe', 'r'], 1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, $root, $environment);
if (! is_resource($process)) {
    throw new RuntimeException('Unable to start the isolated HTTP server.');
}
fclose($pipes[0]);
$client = new Client(['base_uri' => $url, 'cookies' => new CookieJar, 'http_errors' => false, 'allow_redirects' => false, 'timeout' => 30]);
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
    foreach (['upcoming', 'history', 'favorites', 'notifications'] as $page) {
        $response = $client->get('/dashboard/'.$page);
        $html = (string) $response->getBody();
        $expect($response->getStatusCode() === 200 && str_contains($html, 'Account navigation'), 'customer:'.$page);
        $expect(str_contains($response->getHeaderLine('Cache-Control'), 'no-store'), 'private:'.$page);
        $expect(! str_contains($html, 'data-booking-id="'.$foreignBooking->id.'"'), 'ownership:'.$page);
        file_put_contents($directory.'/customer-'.$page.'.html', $html);
    }
    $expect($client->post('/dashboard/bookings/'.$customerBooking->id.'/cancel')->getStatusCode() === 419, 'customer_cancel_requires_csrf');
    preg_match('/name="csrf-token" content="([^"]+)"/', $html, $csrf);
    $expect($client->post('/dashboard/bookings/'.$foreignBooking->id.'/cancel', ['form_params' => ['_token' => $csrf[1]]])->getStatusCode() === 404, 'customer_cannot_cancel_foreign_booking');
    $expect($client->delete('/dashboard/favorites/'.$salon->id)->getStatusCode() === 419, 'favorite_remove_requires_csrf');
    $expect(parse_url($client->get('/dashboard/profile')->getHeaderLine('Location'), PHP_URL_PATH) === '/profile', 'customer_profile_native');
    $expect(parse_url($client->get('/dashboard/change-password')->getHeaderLine('Location'), PHP_URL_PATH) === '/settings/password', 'customer_password_native');
    $response = $client->get('/profile');
    $html = (string) $response->getBody();
    $expect($response->getStatusCode() === 200 && str_contains($html, 'Profile Information'), 'authenticated_profile');
    preg_match('/name="csrf-token" content="([^"]+)"/', $html, $csrf);
    $response = $client->post('/logout', ['form_params' => ['_token' => $csrf[1]]]);
    $expect($response->getStatusCode() === 302, 'csrf_logout');
    $response = $client->get('/dashboard');
    $expect($response->getStatusCode() === 302 && str_ends_with($response->getHeaderLine('Location'), '/login'), 'logout_blocks_dashboard');
    $fixtureLogin = function (User $account, string $credential) use ($client, $expect) {
        $html = (string) $client->get('/login')->getBody();
        preg_match('/name="csrf-token" content="([^"]+)"/', $html, $csrf);
        preg_match('/wire:snapshot="([^"]+)"/', $html, $snapshot);
        $response = $client->post('/livewire/update', ['json' => ['_token' => $csrf[1], 'components' => [[
            'snapshot' => html_entity_decode($snapshot[1], ENT_QUOTES), 'updates' => ['form.email' => $account->email, 'form.password' => $credential],
            'calls' => [['path' => '', 'method' => 'login', 'params' => []]],
        ]]], 'headers' => ['X-Livewire' => 'true', 'Accept' => 'application/json']]);
        $expect($response->getStatusCode() === 200, 'management_session_login:'.$account->getRoleNames()->first());
    };
    foreach ([[$owner, $ownerPassword, 'owner'], [$admin, $adminPassword, 'admin']] as [$account, $credential, $role]) {
        $fixtureLogin($account, $credential);
        foreach (['salons', 'services', 'bookings', 'user-subscriptions'] as $resource) {
            $response = $client->get('/filament/'.$resource);
            $expect($response->getStatusCode() === 200, 'filament:'.$role.':'.$resource);
            $expect(str_contains($response->getHeaderLine('Cache-Control'), 'no-store'), 'filament_private:'.$role.':'.$resource);
            file_put_contents($directory.'/filament-'.$role.'-'.$resource.'.html', (string) $response->getBody());
        }
        foreach (['salons', 'services'] as $resource) {
            $expect($client->get('/filament/'.$resource.'/create')->getStatusCode() === 200, 'filament_create:'.$role.':'.$resource);
        }
        $response = $client->get('/filament/analytics-dashboard');
        $expect($response->getStatusCode() === 200, 'analytics:'.$role);
        $expect(str_contains((string) $response->getBody(), 'Values are estimates'), 'analytics_estimate_label:'.$role);
        if ($role === 'admin') {
            $expect($client->get('/filament/subscription-packages/'.$package->id.'/edit')->getStatusCode() === 200, 'admin_package_edit');
        }
        if ($role === 'owner') {
            $expect($client->get('/filament/users/create')->getStatusCode() === 403, 'owner_cannot_create_users');
        } else {
            $expect($client->get('/filament/users')->getStatusCode() === 200, 'admin_users');
        }
        $expect($client->get('/'.$role)->getStatusCode() === 302, 'legacy_redirect:'.$role);
        $html = (string) $client->get('/profile')->getBody();
        preg_match('/name="csrf-token" content="([^"]+)"/', $html, $csrf);
        $client->post('/logout', ['form_params' => ['_token' => $csrf[1]]]);
    }
    foreach (['/register', '/forgot-password', '/reset-password/synthetic-token'] as $path) {
        $expect($client->get($path)->getStatusCode() === 200, $path);
    }
    foreach (['/', '/salons', '/trending', '/popular', '/services', '/deals', '/about', '/terms-and-condition', '/privacy-policy', '/salon/'.$salon->id, '/sitemap.xml', '/robots.txt'] as $path) {
        $response = $client->get($path);
        $expect($response->getStatusCode() === 200, 'public:'.$path);
        file_put_contents($directory.'/public-'.($path === '/' ? 'home' : str_replace(['/', '.'], '-', trim($path, '/'))).'.html', (string) $response->getBody());
    }
    $chrome = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
    if (is_file($chrome)) {
        $browser = proc_open(['node', __DIR__.'/verify-storefront-browser.mjs', $url, $directory, $chrome, today()->addDay()->format('Y-m-d'), $fixtureFile],
            [0 => ['pipe', 'r'], 1 => ['file', $directory.'/browser.log', 'w'], 2 => ['file', $directory.'/browser.log', 'a']], $browserPipes, $root);
        if (! is_resource($browser)) {
            throw new RuntimeException('Unable to start browser checks.');
        }
        fclose($browserPipes[0]);
        $expect(proc_close($browser) === 0, 'browser_checks');
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
