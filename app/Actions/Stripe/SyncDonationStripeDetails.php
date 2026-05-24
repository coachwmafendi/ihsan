<?php

namespace App\Actions\Stripe;

use App\Models\Donation;
use Stripe\BalanceTransaction;
use Stripe\Charge;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\PaymentMethod;

class SyncDonationStripeDetails
{
    /**
     * @param  array<string, string>  $stripeOptions
     * @return array{payment_intent: StripePaymentIntent, charge_id: string|null}
     */
    public function sync(Donation $donation, ?StripePaymentIntent $paymentIntent = null, array $stripeOptions = []): array
    {
        $shouldRetrieveMissingChargeDetails = $paymentIntent === null;

        $paymentIntent ??= StripePaymentIntent::retrieve([
            'id' => $donation->stripe_payment_intent_id,
            'expand' => ['latest_charge.balance_transaction'],
        ], $stripeOptions);

        [$cardBrand, $paymentMethodType] = $this->paymentMethodDetails($paymentIntent, $stripeOptions);
        [$chargeId, $stripeFee, $processingFee] = $this->chargeDetails($donation, $paymentIntent, $stripeOptions, $shouldRetrieveMissingChargeDetails);

        $donation->update([
            'stripe_charge_id' => $chargeId,
            'stripe_fee' => $stripeFee,
            'processing_fee' => $processingFee,
            'payment_method_brand' => $cardBrand,
            'payment_method_type' => $paymentMethodType,
            'net_amount' => (float) $donation->gross_amount - $stripeFee,
        ]);

        if ($processingFee > 0) {
            $donation->loadMissing('campaign.organization');
            $organizationId = $donation->campaign?->organization_id;

            if ($organizationId !== null) {
                $donation->processingFee()->create([
                    'organization_id' => $organizationId,
                    'fee_amount' => $processingFee,
                    'fee_percentage' => $this->processingFeePercent(),
                    'status' => 'pending',
                ]);
            }
        }

        return [
            'payment_intent' => $paymentIntent,
            'charge_id' => $chargeId,
        ];
    }

    /**
     * @param  array<string, string>  $stripeOptions
     * @return array{0: string|null, 1: string|null}
     */
    private function paymentMethodDetails(StripePaymentIntent $paymentIntent, array $stripeOptions): array
    {
        $paymentMethodId = is_string($paymentIntent->payment_method ?? null)
            ? $paymentIntent->payment_method
            : ($paymentIntent->payment_method->id ?? null);

        if ($paymentMethodId === null) {
            return [null, null];
        }

        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $stripeOptions);
            $type = $paymentMethod->type;

            if ($type === 'card' && $paymentMethod->card !== null) {
                return [$paymentMethod->card->brand, $type];
            }

            return [$type, $type];
        } catch (\Exception $e) {
            return [null, null];
        }
    }

    /**
     * @param  array<string, string>  $stripeOptions
     * @return array{0: string|null, 1: float, 2: float}
     */
    private function chargeDetails(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions, bool $shouldRetrieveMissingChargeDetails): array
    {
        $charge = $paymentIntent->latest_charge ?? ($paymentIntent->charges->data[0] ?? null);
        $charge = $this->retrieveCharge($charge, $stripeOptions, $shouldRetrieveMissingChargeDetails);
        $chargeId = is_string($charge) ? $charge : ($charge->id ?? null);
        $processingFee = round((float) $donation->gross_amount * $this->processingFeePercent() / 100, 2);
        $stripeFee = 0.0;
        $balanceTransaction = is_string($charge) ? null : ($charge->balance_transaction ?? null);

        if ($balanceTransaction !== null) {
            $balanceTransaction = $this->retrieveBalanceTransaction($balanceTransaction, $stripeOptions);
            [$stripeFee, $processingFee] = $this->feesFromBalanceTransaction($balanceTransaction, $processingFee);
        }

        return [$chargeId, $stripeFee, $processingFee];
    }

    /**
     * @param  array<string, string>  $stripeOptions
     */
    private function retrieveCharge(mixed $charge, array $stripeOptions, bool $shouldRetrieveMissingChargeDetails): mixed
    {
        $chargeId = is_string($charge) ? $charge : ($charge->id ?? null);

        if ($chargeId === null) {
            return $charge;
        }

        if (! is_string($charge) && (($charge->balance_transaction ?? null) !== null || ! $shouldRetrieveMissingChargeDetails)) {
            return $charge;
        }

        try {
            return Charge::retrieve([
                'id' => $chargeId,
                'expand' => ['balance_transaction'],
            ], $stripeOptions);
        } catch (\Exception $e) {
            return $charge;
        }
    }

    /**
     * @param  array<string, string>  $stripeOptions
     */
    private function retrieveBalanceTransaction(mixed $balanceTransaction, array $stripeOptions): mixed
    {
        $balanceTransactionId = is_string($balanceTransaction)
            ? $balanceTransaction
            : ($balanceTransaction->id ?? null);

        if ($balanceTransactionId === null) {
            return $balanceTransaction;
        }

        try {
            return BalanceTransaction::retrieve($balanceTransactionId, $stripeOptions);
        } catch (\Exception $e) {
            return $balanceTransaction;
        }
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function feesFromBalanceTransaction(mixed $balanceTransaction, float $fallbackProcessingFee): array
    {
        $feeDetails = collect($balanceTransaction->fee_details ?? []);
        $stripeFee = (float) ($feeDetails
            ->filter(fn (mixed $fee): bool => in_array(data_get($fee, 'type'), ['stripe_fee', 'stripe_processing_fee'], true))
            ->sum(fn (mixed $fee): int => (int) data_get($fee, 'amount', 0)) / 100);
        $processingFee = (float) ($feeDetails
            ->filter(fn (mixed $fee): bool => data_get($fee, 'type') === 'application_fee')
            ->sum(fn (mixed $fee): int => (int) data_get($fee, 'amount', 0)) / 100);

        if ($processingFee <= 0) {
            $processingFee = $fallbackProcessingFee;
        }

        if ($stripeFee <= 0 && ($balanceTransaction->fee ?? 0) > 0) {
            $stripeFee = max((float) ($balanceTransaction->fee / 100) - $processingFee, 0);
        }

        return [$stripeFee, $processingFee];
    }

    private function processingFeePercent(): float
    {
        return (float) config('services.stripe.processing_fee_percent', 2.5);
    }
}
