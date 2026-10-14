<?php

use App\Support\DeploymentReadiness;
use Illuminate\Config\Repository;

function releaseConfiguration(): Repository
{
    return new Repository([
        'app' => ['env' => 'production', 'debug' => false, 'url' => 'https://example.com', 'key' => 'base64:'.base64_encode(str_repeat('k', 32)), 'cipher' => 'AES-256-CBC', 'trusted_proxies' => ['10.0.0.1/32']],
        'session' => ['secure' => true, 'http_only' => true, 'same_site' => 'lax', 'driver' => 'database'],
        'cache' => ['default' => 'database'], 'mail' => ['default' => 'smtp'],
        'database' => ['default' => 'mysql', 'connections' => ['mysql' => ['driver' => 'mysql']]],
        'cors' => ['allowed_origins' => [], 'allowed_origins_patterns' => []],
        'sanctum' => ['expiration' => 1440],
    ]);
}

it('does not claim release readiness even when automated checks pass', function () {
    $report = (new DeploymentReadiness)->report(releaseConfiguration(), dirname(__DIR__, 2).'/public');
    expect($report['automated_pass'])->toBeTrue()
        ->and($report['release_ready'])->toBeFalse()
        ->and(array_unique(array_values($report['manual_gates'])))->toBe(['unverified']);
});

it('reports unsafe settings without disclosing their values', function () {
    $config = releaseConfiguration();
    $config->set('app.debug', true);
    $config->set('app.url', 'https://private-user:private-password@example.com');
    $config->set('app.trusted_proxies', ['*']);
    $config->set('cors.allowed_origins', ['http://localhost:5173']);
    $config->set('mail.default', 'log');
    $report = (new DeploymentReadiness)->report($config, dirname(__DIR__, 2).'/public');
    foreach (['debug_disabled', 'https_application_url', 'explicit_trusted_proxies', 'restricted_cors_origins', 'configured_mail_transport'] as $id) {
        expect($report['automated_checks'][$id])->toBe('fail');
    }
    expect(json_encode($report))->not->toContain('private-password', 'private-user', 'localhost:5173', $config->get('app.key'));
});

it('fails missing release assets rather than treating configuration alone as sufficient', function () {
    $report = (new DeploymentReadiness)->report(releaseConfiguration(), dirname(__DIR__).'/nonexistent-public');
    expect($report['automated_pass'])->toBeFalse()
        ->and($report['automated_checks']['root_build_assets_present'])->toBe('fail')
        ->and($report['automated_checks']['filament_assets_present'])->toBe('fail');
});

it('fails an unbounded token policy left in an old configuration cache', function () {
    $config = releaseConfiguration();
    $config->set('sanctum.expiration', null);
    $report = (new DeploymentReadiness)->report($config, dirname(__DIR__, 2).'/public');
    expect($report['automated_checks']['finite_api_token_lifetime'])->toBe('fail')
        ->and($report['automated_pass'])->toBeFalse();
});

it('rejects wildcard origins and broad proxy networks', function () {
    $config = releaseConfiguration();
    $config->set('cors.allowed_origins', ['*']);
    $config->set('cors.allowed_origins_patterns', ['.*']);
    $config->set('app.trusted_proxies', ['0.0.0.0/0']);
    $report = (new DeploymentReadiness)->report($config, dirname(__DIR__, 2).'/public');
    foreach (['restricted_cors_origins', 'no_cors_origin_patterns', 'explicit_trusted_proxies'] as $id) {
        expect($report['automated_checks'][$id])->toBe('fail');
    }
});
