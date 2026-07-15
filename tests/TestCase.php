<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * The guard lives here (not in setUp) because refreshApplication() runs
     * before setUpTraits(), i.e. before RefreshDatabase can execute
     * migrate:fresh against the wrong connection. A cached config
     * (bootstrap/cache/config.php) silently overrides phpunit.xml's env
     * vars and points the suite at the real local database.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        $this->ensureTestsUseInMemoryDatabase();
    }

    protected function ensureTestsUseInMemoryDatabase(): void
    {
        if (config('database.default') !== 'sqlite'
            || config('database.connections.sqlite.database') !== ':memory:') {
            throw new RuntimeException(sprintf(
                'Tests must run against the in-memory SQLite database, but the [%s] connection is active. '
                .'A cached config is the usual culprit — run "php artisan config:clear" and retry.',
                config('database.default'),
            ));
        }
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
