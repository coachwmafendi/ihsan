<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\ProcessingFee;
use Chip\Model\Purchase;

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
            'status' => $this->mapStatus($purchase->status ?? ''),
            'processing_fee' => $processingFee,
            'net_amount' => ((float) $donation->gross_amount) - $processingFee,
            'payment_method_brand' => $this->extractPaymentMethodBrand($purchase),
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

    private function mapStatus(string $chipStatus): DonationStatus
    {
        return match ($chipStatus) {
            'paid', 'captured' => DonationStatus::Succeeded,
            'failed', 'expired' => DonationStatus::Failed,
            'cancelled' => DonationStatus::Cancelled,
            default => DonationStatus::Pending,
        };
    }

    private function extractPaymentMethodBrand(Purchase $purchase): ?string
    {
        if (isset($purchase->payment) && ! empty($purchase->payment->payment_type)) {
            return $purchase->payment->payment_type;
        }

        if (isset($purchase->purchase->payment_method_details) && is_object($purchase->purchase->payment_method_details)) {
            return $purchase->purchase->payment_method_details->payment_type ?? null;
        }

        return null;
    }
}
