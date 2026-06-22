<?php

use App\Jobs\Middleware\ThrottleMailtrapMiddleware;
use App\Jobs\ProcessStripeWebhook;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\RateLimiter;

function makeJob(string $className): object
{
    return new class($className)
    {
        public function __construct(private string $className) {}

        public int $releasedSeconds = 0;

        public function resolveName(): string
        {
            return $this->className;
        }

        public function release(int $seconds): void
        {
            $this->releasedSeconds = $seconds;
        }
    };
}

beforeEach(function () {
    RateLimiter::clear('mailtrap-send');
    RateLimiter::clear('test-mailtrap-send');
});

it('passes through non-mail jobs immediately', function () {
    config(['mail.rate_limit.enabled' => true]);

    $job = makeJob(ProcessStripeWebhook::class);
    $nextCalled = false;

    app(ThrottleMailtrapMiddleware::class)->handle($job, function () use (&$nextCalled) {
        $nextCalled = true;
    });

    expect($nextCalled)->toBeTrue()
        ->and($job->releasedSeconds)->toBe(0);
});

it('passes the first queued mailable then throttles the second', function () {
    config([
        'mail.rate_limit.enabled' => true,
        'mail.rate_limit.max_attempts' => 1,
        'mail.rate_limit.decay_seconds' => 2,
        'mail.rate_limit.limiter' => 'test-mailtrap-send',
    ]);

    $middleware = app(ThrottleMailtrapMiddleware::class);

    $first = makeJob(SendQueuedMailable::class);
    $firstNextCalled = false;
    $middleware->handle($first, function () use (&$firstNextCalled) {
        $firstNextCalled = true;
    });

    $second = makeJob(SendQueuedMailable::class);
    $secondNextCalled = false;
    $middleware->handle($second, function () use (&$secondNextCalled) {
        $secondNextCalled = true;
    });

    expect($firstNextCalled)->toBeTrue()
        ->and($first->releasedSeconds)->toBe(0)
        ->and($secondNextCalled)->toBeFalse()
        ->and($second->releasedSeconds)->toBeGreaterThan(0);
});

it('is disabled by default', function () {
    config(['mail.rate_limit.enabled' => false]);

    $job = makeJob(SendQueuedMailable::class);
    $nextCalled = false;

    app(ThrottleMailtrapMiddleware::class)->handle($job, function () use (&$nextCalled) {
        $nextCalled = true;
    });

    expect($nextCalled)->toBeTrue();
});
