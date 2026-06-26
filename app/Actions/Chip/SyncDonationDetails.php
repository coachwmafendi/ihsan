<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Models\Donation;
use App\Models\ProcessingFee;

final class SyncDonationDetails
{
    public function sync(Donation $donation): void
    {
        $donation->load('campaign.organization');

        $organization = $donation->campaign->organization;
        $chip = ChipApiFactory::make($organization);

        $purchase = $chip->purchases->get($donation->chip_purchase_id);

        $feePercent = $organization->processing_fee_override
            ?? config('services.chip.processing_fee_percent');

        $processingFeeCents = (int) round(((float) $donation->gross_amount) * ($feePercent / 100) * 100);
        $processingFee = $processingFeeCents / 100;

        $donation->update([
            'status' => $purchase->status === 'paid' ? 'succeeded' : $purchase->status,
            'processing_fee' => $processingFee,
            'net_amount' => ((float) $donation->gross_amount) - $processingFee,
            'payment_method_brand' => property_exists($purchase, 'payment_method') ? $purchase->payment_method : null,
        ]);

        ProcessingFee::updateOrCreate(
            ['donation_id' => $donation->id],
            [
                'organization_id' => $organization->id,
                'fee_amount' => $processingFee,
                'fee_percentage' => $feePercent,
                'status' => 'pending',
            ]
        );
    }
}
