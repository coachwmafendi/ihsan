<?php

declare(strict_types=1);

// Tests must never run against the real local database, even if the shell
// already has DB_CONNECTION=pgsql from the dev .env file.
putenv('DB_CONNECTION=sqlite');
putenv('DB_DATABASE=:memory:');
putenv('DB_URL=');

$_ENV['DB_CONNECTION'] = 'sqlite';
$_SERVER['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = ':memory:';
$_SERVER['DB_DATABASE'] = ':memory:';
$_ENV['DB_URL'] = '';
$_SERVER['DB_URL'] = '';

// Do not let a cached local config leak into the test runtime.
$cacheFile = __DIR__.'/../bootstrap/cache/config.php';

if (is_file($cacheFile)) {
    unlink($cacheFile);
}

require __DIR__.'/../vendor/autoload.php';
