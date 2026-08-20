<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Donation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Spatie\Activitylog\Models\Activity;

#[Signature('app:backfill-audit-log-event-source {--dry-run}')]
#[Description('Relabel donation entries wrongly recorded as coming from the donor portal')]
class BackfillAuditLogEventSource extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // The public checkout used to stamp every donation it created as
        // donor_portal; the donation itself knows where it really came from.
        $activities = Activity::query()
            ->where('subject_type', Donation::class)
            ->where('properties->source', 'donor_portal')
            ->get();

        if ($activities->isEmpty()) {
            $this->info('No mislabelled donation entries found.');

            return self::SUCCESS;
        }

        $updated = 0;
        $skipped = 0;

        foreach ($activities as $activity) {
            $source = Donation::query()->whereKey($activity->subject_id)->value('source');

            if (blank($source)) {
                $this->warn(sprintf('log #%d: donation %d has no source recorded.', $activity->id, $activity->subject_id));
                $skipped++;

                continue;
            }

            $this->line(sprintf('log #%d: donor_portal -> %s', $activity->id, $source));

            if (! $dryRun) {
                $properties = $activity->properties->put('source', $source);
                $activity->properties = $properties;
                $activity->save();
            }

            $updated++;
        }

        $this->newLine();
        $this->info(sprintf('%s %d entr%s; %d skipped.', $dryRun ? 'Would relabel' : 'Relabelled', $updated, $updated === 1 ? 'y' : 'ies', $skipped));

        return self::SUCCESS;
    }
}
