<?php

use App\Jobs\Middleware\ThrottleMailtrapMiddleware;
use App\Jobs\ProcessStripeWebhook;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Facades\Cache;

function makeCommand(string $className): object
{
    return new class($className)
    {
        public function __construct(private string $className) {}

        public function resolveName(): string
        {
            return $this->className;
        }
    };
}

beforeEach(function () {
    Cache::lock('mailtrap-send')->forceRelease();
    Cache::lock('test-mailtrap-send')->forceRelease();
});

it('passes through non-mail jobs immediately', function () {
    config(['mail.rate_limit.enabled' => true]);

    $job = makeCommand(ProcessStripeWebhook::class);
    $nextCalled = false;

    app(ThrottleMailtrapMiddleware::class)->handle($job, function () use (&$nextCalled) {
        $nextCalled = true;
    });

    expect($nextCalled)->toBeTrue();
});

it('allows a queued mailable to run', function () {
    config([
        'mail.rate_limit.enabled' => true,
        'mail.rate_limit.decay_seconds' => 0,
        'mail.rate_limit.limiter' => 'test-mailtrap-send',
    ]);

    $job = makeCommand(SendQueuedMailable::class);
    $nextCalled = false;

    app(ThrottleMailtrapMiddleware::class)->handle($job, function () use (&$nextCalled) {
        $nextCalled = true;
    });

    expect($nextCalled)->toBeTrue();
});

it('is disabled by default', function () {
    config(['mail.rate_limit.enabled' => false]);

    $job = makeCommand(SendQueuedMailable::class);
    $nextCalled = false;

    app(ThrottleMailtrapMiddleware::class)->handle($job, function () use (&$nextCalled) {
        $nextCalled = true;
    });

    expect($nextCalled)->toBeTrue();
});
