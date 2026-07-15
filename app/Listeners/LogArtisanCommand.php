<?php

declare(strict_types=1);

namespace App\Listeners;

use Illuminate\Console\Events\CommandStarting;
use Spatie\Activitylog\Models\Activity;

final class LogArtisanCommand
{
    public function handle(CommandStarting $event): void
    {
        $command = $event->command ?? 'unknown';

        $properties = [
            'command' => $command,
            'input' => (string) $event->input,
            'user' => get_current_user(),
            'hostname' => gethostname() ?: null,
            'pid' => getmypid(),
            'php_sapi' => PHP_SAPI,
        ];

        /** @var Activity $activity */
        $activity = activity()
            ->useLog('artisan')
            ->event('command_starting')
            ->withProperties($properties)
            ->log("Artisan command started: {$command}");

        $activity->save();
    }
}
