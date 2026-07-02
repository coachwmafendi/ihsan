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
        $cardDetails = $this->extractCardDetails($purchase);
        $chipFee = $status === DonationStatus::Succeeded ? $this->extractChipFee($purchase) : 0.0;

        $processingFee = 0.0;
        $feePercent = 0.0;

        if ($status === DonationStatus::Succeeded) {
            [$processingFee, $feePercent] = $this->calculateProcessingFee($donation, $paymentMethod, $organization);
        }

        $updateAttributes = [
            'status' => $status,
            'chip_fee' => $chipFee,
            'processing_fee' => $processingFee,
            'net_amount' => ((float) $donation->gross_amount) - $chipFee - $processingFee,
            'payment_method_brand' => $paymentMethod,
            'payment_method_last4' => $cardDetails['last4'] ?? $donation->payment_method_last4,
            'donor_country' => $cardDetails['country'] ?? $donation->donor_country,
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

    private function extractChipFee(mixed $purchase): float
    {
        $feeAmount = $purchase->payment->fee_amount ?? null;

        if ($feeAmount !== null && is_numeric($feeAmount)) {
            return round((float) $feeAmount / 100, 2);
        }

        return 0.0;
    }

    /**
     * @return array{last4: ?string, country: ?string}
     */
    private function extractCardDetails(mixed $purchase): array
    {
        $transactionData = $purchase->transaction_data ?? null;
        $extra = data_get($transactionData, 'extra');

        if (! is_array($extra) && ! is_object($extra)) {
            return ['last4' => null, 'country' => null];
        }

        $maskedPan = data_get($extra, 'masked_pan');
        $last4 = null;

        if (is_string($maskedPan) && $maskedPan !== '') {
            // Masked PAN format from CHIP: 444433******1111
            preg_match('/(\d{4})$/', $maskedPan, $matches);
            $last4 = $matches[1] ?? null;
        }

        $country = data_get($extra, 'card_issuer_country') ?? data_get($transactionData, 'country');

        return [
            'last4' => $last4,
            'country' => is_string($country) && $country !== '' ? $country : null,
        ];
    }

    /**
     * Calculate the Ihsan platform processing fee. The actual CHIP processor fee
     * (card fee / FPX fee) is recorded separately in {@see extractChipFee()} and
     * stored in the `chip_fee` column.
     *
     * @return array{0: float, 1: float}
     */
    private function calculateProcessingFee(Donation $donation, ?string $paymentMethod, Organization $organization): array
    {
        $grossAmount = (float) $donation->gross_amount;
        $feePercent = $organization->processing_fee_override;

        if ($feePercent === null) {
            $feePercent = $this->isFpx($paymentMethod)
                ? config('services.chip.fpx_processing_fee_percent')
                : config('services.chip.processing_fee_percent');
        }

        $feeAmount = round($grossAmount * ((float) $feePercent / 100), 2);

        return [$feeAmount, (float) $feePercent];
    }

    private function isFpx(?string $paymentMethod): bool
    {
        return $paymentMethod !== null && str_contains(strtolower($paymentMethod), 'fpx');
    }
}
