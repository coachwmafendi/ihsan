<?php

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Console\Command;

/**
 * Put every campaign's collected_amount back in step with its donations.
 *
 * The counter is kept by hand as donations succeed and refunds arrive, so it
 * can drift - a foreign donation counted before its exchange rate existed was
 * counted as ringgit at its foreign figure. That is fixed where it happens,
 * but a counter that has already drifted stays drifted, and a running total
 * nobody can check is a running total nobody should trust.
 */
class RecalculateCampaignCollectedAmount extends Command
{
    protected $signature = 'campaign:recalculate-collected {--dry-run : Report the drift without writing anything}';

    protected $description = 'Recalculate campaign collected_amount from succeeded donations';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $campaigns = Campaign::query()->get();

        $this->info("Checking {$campaigns->count()} campaigns".($dryRun ? ' (dry run)' : '')."...\n");

        $drifted = 0;

        foreach ($campaigns as $campaign) {
            $actual = (float) Donation::query()
                ->where('campaign_id', $campaign->id)
                ->where('status', DonationStatus::Succeeded)
                ->get(['base_amount', 'gross_amount'])
                ->sum(fn (Donation $donation): float => (float) ($donation->base_amount ?? $donation->gross_amount));

            $current = (float) $campaign->collected_amount;

            if (abs($current - $actual) <= 0.01) {
                continue;
            }

            $drifted++;

            $this->line("Campaign #{$campaign->id}: {$campaign->title}");
            $this->line('  Current:  RM '.number_format($current, 2));
            $this->line('  Actual:   RM '.number_format($actual, 2));
            $this->line('  Diff:     RM '.number_format($actual - $current, 2));

            if ($dryRun) {
                $this->comment("  · would update\n");

                continue;
            }

            $campaign->update(['collected_amount' => $actual]);
            $this->info("  ✓ Updated\n");
        }

        $this->info($drifted === 0 ? 'Every campaign is in step.' : "Done. {$drifted} campaign(s) out of step.");

        return self::SUCCESS;
    }
}
