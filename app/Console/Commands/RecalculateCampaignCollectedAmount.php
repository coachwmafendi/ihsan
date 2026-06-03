<?php

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use Illuminate\Console\Command;

class RecalculateCampaignCollectedAmount extends Command
{
    protected $signature = 'campaign:recalculate-collected';

    protected $description = 'Recalculate campaign collected_amount from succeeded donations';

    public function handle(): int
    {
        $campaigns = Campaign::query()
            ->withCount(['donations' => fn ($q) => $q->where('status', DonationStatus::Succeeded)])
            ->get();

        $this->info("Checking {$campaigns->count()} campaigns...\n");

        foreach ($campaigns as $campaign) {
            $actual = Donation::where('campaign_id', $campaign->id)
                ->where('status', DonationStatus::Succeeded)
                ->get()
                ->sum(fn ($d) => (float) ($d->base_amount ?? $d->gross_amount));

            $current = (float) $campaign->collected_amount;
            $actualFloat = (float) $actual;

            if (abs($current - $actualFloat) > 0.01) {
                $this->line("Campaign #{$campaign->id}: {$campaign->title}");
                $this->line('  Current:  RM '.number_format($current, 2));
                $this->line('  Actual:   RM '.number_format($actualFloat, 2));
                $this->line('  Diff:     RM '.number_format($actualFloat - $current, 2));

                $campaign->update(['collected_amount' => $actualFloat]);
                $this->info("  ✓ Updated\n");
            }
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
