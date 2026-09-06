<?php

declare(strict_types=1);

use App\Support\SchedulerLock;
use Illuminate\Support\Facades\Schedule;

/**
 * These commands charge cards, issue invoices and email supporters. On more
 * than one container each must run on exactly one of them, which onOneServer()
 * guarantees only when the mutex lives somewhere every container can see.
 */
it('treats a shared cache store as safe to coordinate on', function (string $store) {
    expect(SchedulerLock::cacheIsSharedAcrossServers($store))->toBeTrue();
})->with(['redis', 'memcached', 'dynamodb']);

it('refuses to coordinate on a store local to one container', function (?string $store) {
    // A file or database mutex cannot reach another container, so pinning on it
    // would be a false promise: both containers would think they held the lock.
    expect(SchedulerLock::cacheIsSharedAcrossServers($store))->toBeFalse();
})->with(['file', 'database', 'array', null]);

it('reads the configured cache store when none is given', function () {
    config(['cache.default' => 'redis']);
    expect(SchedulerLock::cacheIsSharedAcrossServers())->toBeTrue();

    config(['cache.default' => 'database']);
    expect(SchedulerLock::cacheIsSharedAcrossServers())->toBeFalse();
});

it('leaves every scheduled command unpinned while the cache is database backed', function () {
    // Production runs on the database store today, which is why this holds.
    config(['cache.default' => 'database']);

    $events = Schedule::events();

    expect($events)->not->toBeEmpty()
        ->and(collect($events)->filter(fn ($event): bool => $event->onOneServer))->toBeEmpty();
});

it('guards the recurring charge against overlapping itself regardless of store', function () {
    $event = collect(Schedule::events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'ihsan:charge-recurring-plans'));

    expect($event)->not->toBeNull()
        ->and($event->withoutOverlapping)->toBeTrue();
});

it('guards the monthly invoice run against overlapping itself', function () {
    // It creates Stripe invoices from pending fees, so a second run that starts
    // before the first has marked them invoiced would bill the org twice.
    $event = collect(Schedule::events())
        ->first(fn ($event): bool => str_contains((string) $event->command, 'ihsan:generate-monthly-invoices'));

    expect($event)->not->toBeNull()
        ->and($event->withoutOverlapping)->toBeTrue();
});
