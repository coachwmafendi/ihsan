<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\ProcessingFee;
use Chip\Exception\ChipApiException;
use InvalidArgumentException;
use RuntimeException;

final class SyncDonationDetails
{
    public function sync(Donation $donation): void
    {
        if (blank($donation->chip_purchase_id)) {
            return;
        }

        $donation->load('campaign.organization');

        $organization = $donation->campaign?->organization;

        if (! $organization instanceof Organization) {
            throw new RuntimeException('Donation is not linked to an organization.');
        }

        try {
            $chip = ChipApiFactory::make($organization);
            $purchase = $chip->purchases->get($donation->chip_purchase_id);
        } catch (InvalidArgumentException $e) {
            report($e);

            throw new RuntimeException('Failed to initialize CHIP client: '.$e->getMessage(), previous: $e);
        } catch (ChipApiException $e) {
            report($e);

            throw new RuntimeException('Failed to sync CHIP donation details: '.$e->getMessage(), previous: $e);
        }

        $status = $this->mapStatus($purchase->status ?? '');
        $paymentMethod = $this->extractPaymentMethodBrand($purchase);

        $processingFee = 0.0;
        $feePercent = 0.0;

        if ($status === DonationStatus::Succeeded) {
            [$processingFee, $feePercent] = $this->calculateProcessingFee($donation, $paymentMethod, $organization);
        }

        $updateAttributes = [
            'status' => $status,
            'processing_fee' => $processingFee,
            'net_amount' => ((float) $donation->gross_amount) - $processingFee,
            'payment_method_brand' => $paymentMethod,
        ];

        if ($status === DonationStatus::Succeeded
            && $donation->type === DonationType::Recurring
            && filled($purchase->recurring_token ?? null)
        ) {
            $updateAttributes['chip_recurring_token'] = $purchase->recurring_token;
        }

        $donation->update($updateAttributes);

        if ($status !== DonationStatus::Succeeded) {
            return;
        }

        ProcessingFee::updateOrCreate(
            ['donation_id' => $donation->id],
            [
                'organization_id' => $organization->id,
                'fee_amount' => $processingFee,
                'fee_percentage' => $feePercent,
                // CHIP does not support an upfront marketplace split like Stripe Connect,
                // so the platform invoices the organization later and the fee stays pending.
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

        return null;
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function calculateProcessingFee(Donation $donation, ?string $paymentMethod, Organization $organization): array
    {
        $grossAmount = (float) $donation->gross_amount;

        if ($this->isFpx($paymentMethod)) {
            if (config('services.chip.fpx_fee_type') === 'fixed') {
                $feeAmount = ((int) config('services.chip.fpx_fee_amount')) / 100;

                return [round($feeAmount, 2), 0.0];
            }

            $feePercent = (float) config('services.chip.fpx_fee_amount');

            return [round($grossAmount * ($feePercent / 100), 2), $feePercent];
        }

        $feePercent = $organization->processing_fee_override ?? config('services.chip.processing_fee_percent');
        $feeAmount = round($grossAmount * ($feePercent / 100), 2);

        return [$feeAmount, (float) $feePercent];
    }

    private function isFpx(?string $paymentMethod): bool
    {
        return $paymentMethod !== null && str_contains(strtolower($paymentMethod), 'fpx');
    }
}
