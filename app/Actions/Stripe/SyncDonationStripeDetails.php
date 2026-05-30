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
        $paymentIntent ??= StripePaymentIntent::retrieve([
            'id' => $donation->stripe_payment_intent_id,
            'expand' => ['latest_charge.balance_transaction'],
        ], $stripeOptions);

        $pmDetails = $this->paymentMethodDetails($paymentIntent, $stripeOptions);

        $rawCharge = $paymentIntent->latest_charge ?? ($paymentIntent->charges->data[0] ?? null);
        $hasExpandedCharge = ! is_string($rawCharge) && ($rawCharge->balance_transaction ?? null) !== null;
        $shouldRetrieveMissingChargeDetails = ! $hasExpandedCharge;

        [$chargeId, $stripeFee, $processingFee, $baseAmount, $exchangeRate, $feeDetails] = $this->chargeDetails(
            $donation,
            $paymentIntent,
            $stripeOptions,
            $shouldRetrieveMissingChargeDetails,
        );

        $donorFeeCovered = (float) ($donation->donor_fee_covered ?? 0);
        if ($donorFeeCovered > 0 && $exchangeRate !== null) {
            $donorFeeCovered = round($donorFeeCovered * $exchangeRate, 2);
        }

        $donation->update([
            'stripe_charge_id' => $chargeId,
            'stripe_fee' => $stripeFee,
            'processing_fee' => $processingFee,
            'stripe_fee_details' => $feeDetails,
            'payment_method_brand' => $pmDetails['brand'],
            'payment_method_type' => $pmDetails['type'],
            'donor_country' => $pmDetails['card_country'],
            'payment_method_last4' => $pmDetails['last4'],
            'billing_address_line1' => $pmDetails['billing_line1'],
            'billing_address_line2' => $pmDetails['billing_line2'],
            'billing_address_city' => $pmDetails['billing_city'],
            'billing_address_state' => $pmDetails['billing_state'],
            'billing_address_postal_code' => $pmDetails['billing_postal_code'],
            'billing_country' => $pmDetails['billing_country'],
            'base_currency' => 'myr',
            'base_amount' => $baseAmount,
            'exchange_rate' => $exchangeRate,
            'net_amount' => (float) ($baseAmount ?? $donation->gross_amount) + $donorFeeCovered - $stripeFee - $processingFee,
        ]);

        $this->syncDonorAddress($donation, $pmDetails, $paymentIntent);

        if ($processingFee > 0) {
            $donation->loadMissing('campaign.organization');
            $organization = $donation->campaign?->organization;
            $organizationId = $organization?->id;

            if ($organizationId !== null) {
                $status = $organization->fee_collection_method === 'upfront' ? 'collected' : 'pending';

                $donation->processingFee()->updateOrCreate(
                    ['donation_id' => $donation->id],
                    [
                        'organization_id' => $organizationId,
                        'fee_amount' => $processingFee,
                        'fee_percentage' => $this->processingFeePercent(),
                        'status' => $status,
                    ],
                );
            }
        }

        return [
            'payment_intent' => $paymentIntent,
            'charge_id' => $chargeId,
        ];
    }

    /**
     * @param  array<string, string>  $stripeOptions
     * @return array<string, string|null>
     */
    private function paymentMethodDetails(StripePaymentIntent $paymentIntent, array $stripeOptions): array
    {
        $default = [
            'brand' => null,
            'type' => null,
            'card_country' => null,
            'last4' => null,
            'billing_line1' => null,
            'billing_line2' => null,
            'billing_city' => null,
            'billing_state' => null,
            'billing_postal_code' => null,
            'billing_country' => null,
        ];

        $paymentMethodId = is_string($paymentIntent->payment_method ?? null)
            ? $paymentIntent->payment_method
            : ($paymentIntent->payment_method->id ?? null);

        if ($paymentMethodId === null) {
            return $default;
        }

        try {
            $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $stripeOptions);
            $type = $paymentMethod->type;
            $billing = $paymentMethod->billing_details ?? null;

            if ($type === 'card' && $paymentMethod->card !== null) {
                $card = $paymentMethod->card;

                return [
                    'brand' => $card->brand,
                    'type' => $type,
                    'card_country' => $card->country,
                    'last4' => $card->last4,
                    'billing_line1' => $billing?->address?->line1 ?? null,
                    'billing_line2' => $billing?->address?->line2 ?? null,
                    'billing_city' => $billing?->address?->city ?? null,
                    'billing_state' => $billing?->address?->state ?? null,
                    'billing_postal_code' => $billing?->address?->postal_code ?? null,
                    'billing_country' => $billing?->address?->country ?? null,
                ];
            }

            return $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    private function syncDonorAddress(Donation $donation, array $pmDetails, StripePaymentIntent $paymentIntent): void
    {
        $donor = $donation->donor;

        if ($donor === null) {
            return;
        }

        try {
            $charge = $paymentIntent->latest_charge ?? ($paymentIntent->charges->data[0] ?? null);
            $billing = is_string($charge) ? null : ($charge->billing_details ?? null);

            if ($billing === null) {
                return;
            }

            $address = $billing->address ?? null;
            $name = $billing->name ?? null;

            $update = [];

            if ($address !== null) {
                if ($address->line1 ?? null) {
                    $update['address_line1'] = $address->line1;
                }
                if ($address->line2 ?? null) {
                    $update['address_line2'] = $address->line2;
                }
                if ($address->city ?? null) {
                    $update['address_city'] = $address->city;
                }
                if ($address->state ?? null) {
                    $update['address_state'] = $address->state;
                }
                if ($address->postal_code ?? null) {
                    $update['address_postal_code'] = $address->postal_code;
                }
                if ($address->country ?? null) {
                    $update['country'] = $address->country;
                }
            }

            if ($name !== null && blank($donor->name)) {
                $update['name'] = $name;
            }

            if ($update !== []) {
                $donor->update($update);
            }
        } catch (\Exception $e) {
            return;
        }
    }

    /**
     * @param  array<string, string>  $stripeOptions
     * @return array{0: string|null, 1: float, 2: float, 3: float|null, 4: float|null, 5: array<int, array<string, mixed>>|null}
     */
    private function chargeDetails(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions, bool $shouldRetrieveMissingChargeDetails): array
    {
        $charge = $paymentIntent->latest_charge ?? ($paymentIntent->charges->data[0] ?? null);
        $charge = $this->retrieveCharge($charge, $stripeOptions, $shouldRetrieveMissingChargeDetails);
        $chargeId = is_string($charge) ? $charge : ($charge->id ?? null);

        $baseAmount = (float) $donation->gross_amount;
        $exchangeRate = null;

        if (strtolower($donation->currency) !== 'myr') {
            $balanceTransaction = is_string($charge) ? null : ($charge->balance_transaction ?? null);
            if ($balanceTransaction !== null) {
                $balanceTransaction = $this->retrieveBalanceTransaction($balanceTransaction, $stripeOptions);
                if ($balanceTransaction->exchange_rate !== null) {
                    $exchangeRate = (float) $balanceTransaction->exchange_rate;
                    $baseAmount = round(((float) $donation->gross_amount) * $exchangeRate, 2);
                } else {
                    $baseAmount = 0;
                }
            } else {
                $baseAmount = 0;
            }
        }

        $baseAmount = $baseAmount > 0 ? $baseAmount : null;

        $processingFee = round((float) ($baseAmount ?? $donation->gross_amount) * $this->processingFeePercent() / 100, 2);
        $stripeFee = 0.0;
        $feeDetails = null;
        $balanceTransaction = is_string($charge) ? null : ($charge->balance_transaction ?? null);

        if ($balanceTransaction !== null) {
            $balanceTransaction = $this->retrieveBalanceTransaction($balanceTransaction, $stripeOptions);
            [$stripeFee, $processingFee, $feeDetails] = $this->feesFromBalanceTransaction($balanceTransaction, $processingFee);
        }

        return [$chargeId, $stripeFee, $processingFee, $baseAmount, $exchangeRate, $feeDetails];
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
     * @return array{0: float, 1: float, 2: array<int, array<string, mixed>>|null}
     */
    private function feesFromBalanceTransaction(mixed $balanceTransaction, float $fallbackProcessingFee): array
    {
        $rawFeeDetails = $balanceTransaction->fee_details ?? [];
        $feeDetails = collect($rawFeeDetails);
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

        $savedFeeDetails = array_map(fn (mixed $fee): array => [
            'type' => data_get($fee, 'type'),
            'amount' => data_get($fee, 'amount'),
            'currency' => data_get($fee, 'currency'),
            'description' => data_get($fee, 'description'),
        ], $rawFeeDetails);

        return [$stripeFee, $processingFee, $savedFeeDetails];
    }

    private function processingFeePercent(): float
    {
        return (float) config('services.stripe.processing_fee_percent', 2.5);
    }
}
