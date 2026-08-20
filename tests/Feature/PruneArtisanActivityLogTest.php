<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Organization;
use App\Services\AuditLogLogger;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\Activitylog\Models\Activity;

function artisanNoise(int $count, ?string $createdAt = null): void
{
    foreach (range(1, $count) as $i) {
        activity()
            ->useLog('artisan')
            ->event('command_starting')
            ->log("Artisan command started: ihsan:charge-recurring-plans {$i}");
    }

    if ($createdAt !== null) {
        Activity::query()->where('log_name', 'artisan')->update(['created_at' => $createdAt]);
    }
}

it('deletes artisan command entries but leaves real audit history alone', function () {
    $campaign = Campaign::factory()->for(Organization::factory())->create();
    AuditLogLogger::log($campaign, 'updated', 'Campaign updated.');

    artisanNoise(3);

    $realEntries = Activity::query()->where('log_name', '!=', 'artisan')->count();

    expect(Activity::query()->where('log_name', 'artisan')->count())->toBe(3);

    test()->artisan('app:prune-artisan-activity-log')->assertExitCode(0);

    expect(Activity::query()->where('log_name', 'artisan')->count())->toBe(0)
        ->and(Activity::query()->where('log_name', '!=', 'artisan')->count())->toBe($realEntries)
        ->and(Activity::query()->where('description', 'Campaign updated.')->exists())->toBeTrue();
});

it('writes nothing on a dry run', function () {
    artisanNoise(3);

    test()->artisan('app:prune-artisan-activity-log', ['--dry-run' => true])->assertExitCode(0);

    expect(Activity::query()->where('log_name', 'artisan')->count())->toBe(3);
});

it('can keep recent entries for debugging the scheduler', function () {
    artisanNoise(2, now()->subDays(40)->toDateTimeString());
    artisanNoise(3);

    test()->artisan('app:prune-artisan-activity-log', ['--keep-days' => 30])->assertExitCode(0);

    expect(Activity::query()->where('log_name', 'artisan')->count())->toBe(3);
});

it('says so when there is nothing to prune', function () {
    test()->artisan('app:prune-artisan-activity-log')
        ->expectsOutputToContain('No artisan command entries to prune.')
        ->assertExitCode(0);
});

it('schedules the activity log cleanup so retention is actually applied', function () {
    $commands = collect(app(Schedule::class)->events())
        ->map(fn ($event): string => (string) $event->command);

    expect($commands->contains(fn (string $command): bool => str_contains($command, 'activitylog:clean')))->toBeTrue()
        ->and(config('activitylog.clean_after_days'))->toBe(365);
});
