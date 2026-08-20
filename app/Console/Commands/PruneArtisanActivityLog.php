<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

#[Signature('app:prune-artisan-activity-log {--dry-run} {--keep-days=}')]
#[Description('Delete command_starting entries written by scheduled commands')]
class PruneArtisanActivityLog extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $keepDays = $this->option('keep-days') !== null ? (int) $this->option('keep-days') : null;

        $query = Activity::query()
            ->where('log_name', 'artisan')
            ->where('event', 'command_starting');

        if ($keepDays !== null) {
            $query->where('created_at', '<', now()->subDays($keepDays));
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No artisan command entries to prune.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info(sprintf('Would delete %s artisan command entr%s.', number_format($total), $total === 1 ? 'y' : 'ies'));

            return self::SUCCESS;
        }

        // Delete in chunks so a table with hundreds of thousands of rows does
        // not hold one enormous transaction open.
        $deleted = 0;

        do {
            $chunk = (clone $query)->limit(5000)->pluck('id');

            if ($chunk->isEmpty()) {
                break;
            }

            $deleted += Activity::query()->whereIn('id', $chunk)->delete();
        } while (true);

        $this->info(sprintf('Deleted %s artisan command entr%s.', number_format($deleted), $deleted === 1 ? 'y' : 'ies'));

        return self::SUCCESS;
    }
}
