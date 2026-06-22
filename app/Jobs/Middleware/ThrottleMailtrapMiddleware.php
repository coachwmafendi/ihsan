<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Cache;

class ThrottleMailtrapMiddleware
{
    public function handle(object $job, Closure $next): mixed
    {
        if (! $this->shouldThrottle($job)) {
            return $next($job);
        }

        $lockKey = config('mail.rate_limit.limiter', 'mailtrap-send');
        $gapSeconds = (int) config('mail.rate_limit.decay_seconds', 5);
        $lockTimeout = max(60, $gapSeconds * 10);

        $lock = Cache::lock($lockKey, $lockTimeout);

        try {
            $lock->block($lockTimeout);

            $result = $next($job);

            if ($gapSeconds > 0) {
                sleep($gapSeconds);
            }

            return $result;
        } finally {
            $lock->release();
        }
    }

    private function shouldThrottle(object $job): bool
    {
        if (! config('mail.rate_limit.enabled', false)) {
            return false;
        }

        $className = $this->resolveClassName($job);

        if (in_array($className, [SendQueuedMailable::class, SendQueuedNotifications::class], true)) {
            return true;
        }

        $additional = config('mail.rate_limit.job_classes', []);

        return in_array($className, $additional, true);
    }

    private function resolveClassName(object $job): string
    {
        if ($job instanceof JobContract) {
            $payload = $job->payload();

            return $payload['data']['commandName'] ?? $job->resolveName();
        }

        if (method_exists($job, 'resolveName')) {
            return $job->resolveName();
        }

        return $job::class;
    }
}
