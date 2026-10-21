<?php

declare(strict_types=1);
use App\Models\Salon;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\FreshInstallationService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

if (PHP_SAPI !== 'cli') {
    exit(1);
}
ini_set('memory_limit', '512M');
$root = dirname(__DIR__, 2);
$paths = json_decode(file_get_contents($root.'/.local/cpanel-current.json'), true, 512, JSON_THROW_ON_ERROR);
$environment = [
    'APP_ENV' => 'testing', 'APP_DEBUG' => 'false', 'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
    'APP_URL' => 'https://bookmystyle.limach.lk', 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:',
    'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'MAIL_MAILER' => 'array', 'QUEUE_CONNECTION' => 'sync',
    'PUSH_ENABLED' => 'false', 'BOOKING_REMINDERS_ENABLED' => 'false', 'FEATURED_CLEANUP_ENABLED' => 'false',
    'SUBSCRIPTION_REMINDERS_ENABLED' => 'false', 'BCRYPT_ROUNDS' => '4',
    'APP_CONFIG_CACHE' => 'bootstrap/cache/config.php',
];
foreach ($environment as $key => $value) {
    putenv($key.'='.$value);
    $_ENV[$key] = $_SERVER[$key] = $value;
}
putenv('DB_URL');
unset($_ENV['DB_URL'], $_SERVER['DB_URL']);
require $paths['app'].'/vendor/autoload.php';
$app = require $paths['app'].'/bootstrap/app.php';
$app->usePublicPath($paths['web']);
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
if (config('database.default') !== 'sqlite' || config('database.connections.sqlite.database') !== ':memory:') {
    throw new RuntimeException('In-memory verification guard failed.');
}
if ($kernel->call('migrate', ['--force' => true]) !== 0) {
    throw new RuntimeException('Fresh migrations failed.');
}
$admin = app(FreshInstallationService::class)->initialize([
    'name' => 'Isolated verification', 'email' => 'cpanel-fixture@example.test', 'password' => 'isolated-fixture-password',
]);
if (! $admin->hasRole('admin') || ! $admin->hasVerifiedEmail() || User::count() !== 1
    || Salon::count() !== 0 || ! SubscriptionPackage::where('slug', 'pro-salon')->first()->isAvailable()) {
    throw new RuntimeException('Fresh initialization verification failed.');
}
echo "PASS: packaged production dependencies, fresh SQLite migrations and administrator initialization.\n";
$http = $app->make(Illuminate\Contracts\Http\Kernel::class);
foreach (['/' => 200, '/filament/login' => 200, '/api/v1/catalogue/salons?per_page=1' => 200, '/api/auth/me' => 401] as $path => $status) {
    $request = Request::create('https://bookmystyle.limach.lk'.$path, 'GET', [], [], [], ['HTTP_ACCEPT' => str_starts_with($path, '/api/') ? 'application/json' : 'text/html']);
    $response = $http->handle($request);
    if ($response->getStatusCode() !== $status) {
        throw new RuntimeException('Packaged HTTP smoke check failed: '.$path.' status '.$response->getStatusCode());
    }
    echo 'PASS HTTP '.$status.' '.$path.PHP_EOL;
    $http->terminate($request, $response);
}
foreach (['config:cache', 'route:cache', 'view:cache'] as $command) {
    if ($kernel->call($command) !== 0) {
        throw new RuntimeException('Packaged cache check failed.');
    }
    echo 'PASS '.$command.PHP_EOL;
}
// Isolated verification caches must not enter the upload archive.
foreach (['config.php', 'routes-v7.php'] as $file) {
    if (is_file($paths['app'].'/bootstrap/cache/'.$file)) {
        unlink($paths['app'].'/bootstrap/cache/'.$file);
    }
}
