<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Console\Events\CommandStarting;

final class LogArtisanCommand
{
    /**
     * Commands fired constantly by the scheduler and queue workers —
     * logging them would flood the activity log with noise.
     *
     * @var list<string>
     */
    private const array SKIPPED_COMMANDS = [
        'schedule:run',
        'schedule:finish',
        'schedule:work',
        'queue:work',
        'queue:listen',
        'queue:restart',
        'pail',
    ];

    public function handle(CommandStarting $event): void
    {
        $command = $event->command ?? 'unknown';

        if (in_array($command, self::SKIPPED_COMMANDS, true)) {
            return;
        }

        $properties = [
            'command' => $command,
            'input' => (string) $event->input,
            'user' => get_current_user(),
            'hostname' => gethostname() ?: null,
            'pid' => getmypid(),
            'php_sapi' => PHP_SAPI,
        ];

        // The write must never break the command itself: on a fresh database
        // the activity_log table does not exist until migrate has run, and
        // build environments (composer package:discover) may have no DB at all.
        rescue(function () use ($command, $properties): void {
            activity()
                ->useLog('artisan')
                ->event('command_starting')
                ->withProperties($properties)
                ->log("Artisan command started: {$command}");
        }, report: false);
    }
}
