<?php

declare(strict_types=1);

it('fails with a readable message when redis cannot be reached', function () {
    // A port nothing listens on stands in for a misconfigured or dead Redis.
    config([
        'database.redis.default.host' => '127.0.0.1',
        'database.redis.default.port' => 6399,
        'database.redis.default.max_retries' => 0,
    ]);

    test()->artisan('app:check-redis')
        ->expectsOutputToContain('Redis is unreachable')
        ->expectsOutputToContain('127.0.0.1:6399')
        ->assertExitCode(1);
});

it('refuses to guess when the phpredis extension is missing', function () {
    config(['database.redis.client' => 'phpredis']);

    if (extension_loaded('redis')) {
        test()->markTestSkipped('phpredis is loaded here, so the missing-extension path cannot be exercised.');
    }

    test()->artisan('app:check-redis')
        ->expectsOutputToContain('phpredis extension is not loaded')
        ->assertExitCode(1);
});
