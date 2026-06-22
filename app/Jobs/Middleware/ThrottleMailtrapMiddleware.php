<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Contracts\Queue\Job as JobContract;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\RateLimiter;

class ThrottleMailtrapMiddleware
{
    public function handle(object $job, Closure $next): void
    {
        if (! $this->shouldThrottle($job)) {
            $next($job);

            return;
        }

        $limiterKey = config('mail.rate_limit.limiter', 'mailtrap-send');
        $maxAttempts = (int) config('mail.rate_limit.max_attempts', 1);
        $decaySeconds = (int) config('mail.rate_limit.decay_seconds', 2);

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            $availableIn = RateLimiter::availableIn($limiterKey);
            $this->release($job, $availableIn + 1);

            return;
        }

        RateLimiter::hit($limiterKey, $decaySeconds);
        $next($job);
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
        if ($job instanceof JobContract || method_exists($job, 'resolveName')) {
            return $job->resolveName();
        }

        return $job::class;
    }

    private function release(object $job, int $seconds): void
    {
        if ($job instanceof JobContract) {
            $job->release($seconds);

            return;
        }

        if (method_exists($job, 'release')) {
            $job->release($seconds);
        }
    }
}
