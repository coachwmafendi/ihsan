<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;

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

        // A command the scheduler runs is a heartbeat, not something a person
        // did, and logging every tick buries the entries that matter.
        if (self::isScheduled($command)) {
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

    /**
     * Whether this command is one the scheduler runs on its own.
     *
     * Read from the schedule itself so a newly scheduled command cannot start
     * flooding the log just because nobody remembered to add it to a list.
     */
    private static function isScheduled(string $command): bool
    {
        return rescue(function () use ($command): bool {
            foreach (app(Schedule::class)->events() as $event) {
                if (self::scheduledCommandName((string) $event->command) === $command) {
                    return true;
                }
            }

            return false;
        }, rescue: false, report: false);
    }

    /**
     * Pull the command name out of a scheduled event's full shell string,
     * which reads: '<php binary>' 'artisan' <command> [arguments].
     */
    private static function scheduledCommandName(string $scheduled): ?string
    {
        if (preg_match("/'artisan'\s+(\S+)/", $scheduled, $matches) !== 1) {
            return null;
        }

        return trim($matches[1], '\'"');
    }
}
