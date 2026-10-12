<?php

declare(strict_types=1);

use App\Support\DeploymentReadiness;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;

require dirname(__DIR__, 2).'/vendor/autoload.php';

try {
    $app = require dirname(__DIR__, 2).'/bootstrap/app.php';
    // Do not boot providers: provider discovery can write cache manifests.
    $app->bootstrapWith([LoadEnvironmentVariables::class, LoadConfiguration::class]);
    $report = (new DeploymentReadiness)->report($app->make('config'), $app->publicPath());
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    // Strict mode checks automated prerequisites only. Manual release gates remain unverified.
    exit(in_array('--strict', $argv, true) && ! $report['automated_pass'] ? 1 : 0);
} catch (Throwable) {
    // Bootstrap exceptions can contain credentials or connection strings.
    fwrite(STDERR, "Readiness check could not initialize; inspect configuration privately.\n");
    exit(2);
}
