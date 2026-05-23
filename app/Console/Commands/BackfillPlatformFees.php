<?php

namespace App\Console\Commands;

use App\Models\Donation;
use App\Models\PlatformFee;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:backfill-platform-fees')]
#[Description('Create PlatformFee records for existing donations that have platform_fee > 0 but no record yet')]
class BackfillPlatformFees extends Command
{
    public function handle()
    {
        $donations = Donation::query()
            ->with('campaign.organization')
            ->where('platform_fee', '>', 0)
            ->whereDoesntHave('platformFee')
            ->lazy();

        $count = 0;

        foreach ($donations as $donation) {
            PlatformFee::create([
                'donation_id' => $donation->id,
                'organization_id' => $donation->campaign?->organization_id,
                'fee_amount' => $donation->platform_fee,
                'fee_percentage' => config('services.stripe.platform_fee_percent', 2.5),
                'status' => 'pending',
            ]);

            $count++;
        }

        $this->info("Created {$count} platform fee records.");
    }
}
