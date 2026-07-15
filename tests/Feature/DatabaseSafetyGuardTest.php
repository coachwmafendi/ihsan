<?php

it('runs the suite against the in-memory sqlite database', function () {
    expect(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:');
});

it('refuses to run when the default connection is not sqlite', function () {
    try {
        config(['database.default' => 'pgsql']);

        expect(fn () => $this->ensureTestsUseInMemoryDatabase())
            ->toThrow(RuntimeException::class, 'in-memory SQLite');
    } finally {
        config(['database.default' => 'sqlite']);
    }
});

it('refuses to run when sqlite is not in memory', function () {
    try {
        config(['database.connections.sqlite.database' => database_path('database.sqlite')]);

        expect(fn () => $this->ensureTestsUseInMemoryDatabase())
            ->toThrow(RuntimeException::class, 'in-memory SQLite');
    } finally {
        config(['database.connections.sqlite.database' => ':memory:']);
    }
});
