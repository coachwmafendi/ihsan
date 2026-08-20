<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Throwable;

#[Signature('app:check-redis')]
#[Description('Verify Redis is reachable and actually serving the cache')]
class CheckRedisConnection extends Command
{
    public function handle(): int
    {
        if (! extension_loaded('redis') && config('database.redis.client') === 'phpredis') {
            $this->error('The phpredis extension is not loaded. Rebuild the image or set REDIS_CLIENT=predis.');

            return self::FAILURE;
        }

        try {
            $pong = Redis::connection()->ping();
        } catch (Throwable $e) {
            $this->error('Redis is unreachable: '.$e->getMessage());
            $this->line('Host: '.config('database.redis.default.host').':'.config('database.redis.default.port'));

            return self::FAILURE;
        }

        $this->info('Redis responded to PING: '.(is_bool($pong) ? var_export($pong, true) : (string) $pong));

        // Reaching Redis is not the same as using it: the cache store can still
        // be pointed somewhere else, which is the failure that goes unnoticed.
        $store = config('cache.default');
        $this->line('Cache store: '.$store);

        $key = 'redis-health-check:'.uniqid();

        try {
            Cache::put($key, 'ok', 10);
            $roundTripped = Cache::get($key) === 'ok';
            Cache::forget($key);
        } catch (Throwable $e) {
            $this->error('Cache write failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $roundTripped) {
            $this->error('Cache did not return what was written to it.');

            return self::FAILURE;
        }

        $this->info('Cache read/write succeeded.');

        if ($store !== 'redis') {
            $this->warn("Redis is reachable but the cache store is \"{$store}\", so nothing is using it yet.");
        }

        return self::SUCCESS;
    }
}
