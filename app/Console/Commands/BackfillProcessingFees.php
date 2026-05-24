<?php

namespace App\Console\Commands;

use App\Models\Donation;
use App\Models\ProcessingFee;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:backfill-processing-fees')]
#[Description('Create ProcessingFee records for existing donations that have processing_fee > 0 but no record yet')]
class BackfillProcessingFees extends Command
{
    public function handle()
    {
        $donations = Donation::query()
            ->with('campaign.organization')
            ->where('processing_fee', '>', 0)
            ->whereDoesntHave('processingFee')
            ->lazy();

        $count = 0;

        foreach ($donations as $donation) {
            ProcessingFee::create([
                'donation_id' => $donation->id,
                'organization_id' => $donation->campaign?->organization_id,
                'fee_amount' => $donation->processing_fee,
                'fee_percentage' => config('services.stripe.processing_fee_percent', 2.5),
                'status' => 'pending',
            ]);

            $count++;
        }

        $this->info("Created {$count} processing fee records.");
    }
}
