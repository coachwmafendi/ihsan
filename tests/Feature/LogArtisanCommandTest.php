<?php

declare(strict_types=1);

use App\Listeners\LogArtisanCommand;
use Illuminate\Console\Events\CommandStarting;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

function fireCommandStarting(string $command): void
{
    app(LogArtisanCommand::class)->handle(
        new CommandStarting($command, new ArrayInput([]), new NullOutput)
    );
}

it('logs a command a person ran', function () {
    fireCommandStarting('migrate');

    expect(Activity::query()->where('event', 'command_starting')->count())->toBe(1)
        ->and(Activity::query()->latest('id')->value('description'))
        ->toBe('Artisan command started: migrate');
});

it('ignores commands the scheduler runs on its own', function (string $command) {
    fireCommandStarting($command);

    expect(Activity::query()->where('event', 'command_starting')->count())->toBe(0);
})->with([
    // Ran every minute: on its own it produced 99% of the production log.
    'ihsan:charge-recurring-plans',
    'ihsan:send-daily-summary',
    'app:sync-payouts',
    'queue:retry',
]);

it('ignores the scheduler and queue workers themselves', function (string $command) {
    fireCommandStarting($command);

    expect(Activity::query()->where('event', 'command_starting')->count())->toBe(0);
})->with(['schedule:run', 'schedule:work', 'queue:work', 'pail']);

it('does not confuse a scheduled command with one that shares its prefix', function () {
    // queue:retry is scheduled; queue:retry-batch is not.
    fireCommandStarting('queue:retry-batch');

    expect(Activity::query()->where('event', 'command_starting')->count())->toBe(1);
});
