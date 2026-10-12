<?php

it('denies external origins by default in production and honors explicit reviewed origins', function () {
    $names = ['APP_ENV', 'CORS_ALLOWED_ORIGINS'];
    $previous = [];
    foreach ($names as $name) {
        $previous[$name] = [$_ENV[$name] ?? null, $_SERVER[$name] ?? null, getenv($name)];
    }
    $set = static function (string $name, ?string $value): void {
        if ($value === null) {
            unset($_ENV[$name], $_SERVER[$name]);
            putenv($name);
        } else {
            $_ENV[$name] = $_SERVER[$name] = $value;
            putenv($name.'='.$value);
        }
    };
    try {
        $set('APP_ENV', 'production');
        $set('CORS_ALLOWED_ORIGINS', null);
        $config = require dirname(__DIR__, 2).'/config/cors.php';
        expect($config['allowed_origins'])->toBe([]);
        $set('CORS_ALLOWED_ORIGINS', 'https://example.com');
        $config = require dirname(__DIR__, 2).'/config/cors.php';
        expect($config['allowed_origins'])->toBe(['https://example.com']);
        $set('CORS_ALLOWED_ORIGINS', null);
        $set('APP_ENV', 'local');
        $config = require dirname(__DIR__, 2).'/config/cors.php';
        expect($config['allowed_origins'])->toContain('http://localhost:5173');
    } finally {
        foreach ($previous as $name => [$environment, $server, $process]) {
            if ($environment === null) {
                unset($_ENV[$name]);
            } else {
                $_ENV[$name] = $environment;
            }
            if ($server === null) {
                unset($_SERVER[$name]);
            } else {
                $_SERVER[$name] = $server;
            }
            putenv($process === false ? $name : $name.'='.$process);
        }
    }
});
