<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Whether scheduled commands can coordinate across containers.
 *
 * onOneServer() keeps a mutex in the cache store, so it only means anything
 * when every container reads the same one. On a per-container store the call
 * would be a false promise: two containers would each believe they held the
 * lock and both would run — which, for a command that charges cards, means
 * charging twice.
 */
final class SchedulerLock
{
    /**
     * Cache stores that live inside a single container.
     *
     * @var list<string|null>
     */
    private const array LOCAL_STORES = ['file', 'database', 'array', null];

    public static function cacheIsSharedAcrossServers(?string $store = null): bool
    {
        $store ??= config('cache.default');

        return ! in_array($store, self::LOCAL_STORES, true);
    }
}
