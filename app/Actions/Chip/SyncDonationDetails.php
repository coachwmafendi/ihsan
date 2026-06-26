<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
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
            'paid', 'cleared', 'settled' => DonationStatus::Succeeded,
            'error', 'blocked', 'expired', 'chargeback' => DonationStatus::Failed,
            'cancelled' => DonationStatus::Cancelled,
            'refunded' => DonationStatus::Refunded,
            'created', 'sent', 'viewed', 'overdue', 'hold', 'released',
            'pending_release', 'pending_capture', 'preauthorized',
            'pending_execute', 'pending_charge', 'pending_refund' => DonationStatus::Pending,
            default => DonationStatus::Pending,
        };
    }

    private function extractPaymentMethodBrand(mixed $purchase): ?string
    {
        $transactionData = $purchase->transaction_data ?? null;

        if (isset($transactionData->payment_method) && $transactionData->payment_method !== '') {
            return $transactionData->payment_method;
        }

        if (isset($purchase->purchase->payment_method_details) && is_object($purchase->purchase->payment_method_details)) {
            return $purchase->purchase->payment_method_details->payment_method ?? null;
        }

        return null;
    }
}
