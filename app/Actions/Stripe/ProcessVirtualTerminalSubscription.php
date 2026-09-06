<?php

namespace App\Actions\Stripe;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendDonorNewSubscriptionNotification;
use App\Jobs\SendNewSubscriptionNotification;
use App\Jobs\SyncDonationStripeDetailsJob;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\DonationFeeEstimator;
use App\Services\StripeMetadata;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\PaymentMethod;
use Stripe\Price;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

class ProcessVirtualTerminalSubscription
{
    public function handle(
        int $campaignId,
        float $amount,
        string $firstName,
        string $lastName,
        string $email,
        Organization $organization,
        string $currency = 'myr',
        ?string $savedCardId = null,
        ?string $paymentMethodId = null,
        string $source = 'virtual_terminal',
        bool $coverFee = false,
    ): Subscription {
        Stripe::setApiKey(config('services.stripe.secret'));

        $feeCoverAmount = $coverFee ? DonationFeeEstimator::estimate($amount, $currency, 'stripe') : 0.0;
        $chargedAmount = $amount + $feeCoverAmount;

        $campaign = Campaign::query()
            ->where('id', $campaignId)
            ->where('organization_id', $organization->getKey())
            ->firstOrFail();

        $donor = $this->resolveOrCreateDonor($firstName, $lastName, $email);

        $stripeOptions = $organization->stripeOptions();

        try {
            $customerId = app(ResolveDonorStripeCustomer::class)
                ->resolve($donor, $organization, 'virtual_terminal_subscription');

            $unitAmount = (int) round($chargedAmount * 100);

            $existingPrices = Price::all([
                'product' => $campaign->stripe_product_id,
                'unit_amount' => $unitAmount,
                'currency' => strtolower($currency),
            ], $stripeOptions);

            $price = $existingPrices->data[0] ?? null;

            if ($price && $price->recurring->interval !== 'month') {
                $price = null;
            }

            if (! $price) {
                $price = Price::create([
                    'unit_amount' => $unitAmount,
                    'currency' => strtolower($currency),
                    'recurring' => ['interval' => 'month'],
                    'product' => $campaign->stripe_product_id,
                    'metadata' => StripeMetadata::forPrice(
                        campaign: $campaign,
                        organization: $organization,
                        amount: $amount,
                        currency: $currency,
                        interval: 'month',
                        type: 'donation',
                    ),
                ], $stripeOptions);
            }

            if ($paymentMethodId) {
                $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $stripeOptions);
                $paymentMethod->attach(['customer' => $customerId], $stripeOptions);
            }

            $subscriptionParams = [
                'customer' => $customerId,
                'items' => [['price' => $price->id]],
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => StripeMetadata::forVirtualTerminalSubscription(
                    campaign: $campaign,
                    donor: $donor,
                    organization: $organization,
                    amount: $amount,
                    currency: $currency,
                ),
            ];

            if ($savedCardId) {
                $subscriptionParams['default_payment_method'] = $savedCardId;
            } elseif ($paymentMethodId) {
                $subscriptionParams['default_payment_method'] = $paymentMethodId;
            }

            if ($organization->stripe_account_id && $organization->fee_collection_method === 'upfront') {
                $feePercent = (float) config('services.stripe.processing_fee_percent', 2.5);

                // Stripe applies this percentage to the whole invoice, which
                // includes the fee cover. Scale it back down so the fee still
                // works out to our rate on the donation alone. Stripe accepts
                // at most two decimal places, so this lands within a cent.
                if ($chargedAmount > 0 && $feeCoverAmount > 0) {
                    $feePercent = round($feePercent * $amount / $chargedAmount, 2);
                }

                $subscriptionParams['application_fee_percent'] = $feePercent;
            }

            $stripeSubscription = StripeSubscription::create($subscriptionParams, $stripeOptions);
        } catch (CardException $e) {
            throw new \RuntimeException('Card declined: '.$e->getMessage(), previous: $e);
        } catch (InvalidRequestException $e) {
            throw new \RuntimeException('Invalid payment request: '.$e->getMessage(), previous: $e);
        } catch (ApiErrorException $e) {
            throw new \RuntimeException('Payment service error: '.$e->getMessage(), previous: $e);
        }

        $subscription = Subscription::create([
            'campaign_id' => $campaign->getKey(),
            'donor_id' => $donor->getKey(),
            'source' => $source,
            'amount' => $amount,
            'currency' => strtolower($currency),
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
            'cover_fee' => $coverFee,
            'fee_cover_amount' => $feeCoverAmount,
            'stripe_subscription_id' => $stripeSubscription->id,
            'stripe_price_id' => $price->id,
            'started_at' => now(),
            'current_period_start' => now(),
        ]);

        StripeSubscription::update($stripeSubscription->id, [
            'metadata' => StripeMetadata::forVirtualTerminalSubscription(
                campaign: $campaign,
                donor: $donor,
                organization: $organization,
                amount: $amount,
                currency: $currency,
                subscription: $subscription,
            ),
        ], $stripeOptions);

        // Sync payment method details to local cache
        $pmId = $paymentMethodId ?? $savedCardId;
        $this->syncPaymentMethod($donor, $pmId, $stripeOptions);

        $this->recordFirstInstallment(
            $stripeSubscription,
            $subscription,
            $amount,
            $feeCoverAmount,
            $currency,
            $source,
            $stripeOptions,
        );

        return $subscription;
    }

    /**
     * Record the subscription's first paid invoice as a donation and notify the
     * organisation. Keyed on the payment intent so the invoice webhook, which
     * skips donations that already exist, does not create a duplicate.
     *
     * @param  array<string, string>  $stripeOptions
     */
    private function recordFirstInstallment(
        StripeSubscription $stripeSubscription,
        Subscription $subscription,
        float $amount,
        float $feeCoverAmount,
        string $currency,
        string $source,
        array $stripeOptions,
    ): void {
        $invoice = $stripeSubscription->latest_invoice ?? null;

        if (! is_object($invoice)) {
            return;
        }

        $paymentIntent = $invoice->payment_intent ?? null;
        $paymentIntentId = is_object($paymentIntent) ? $paymentIntent->id : $paymentIntent;

        if (blank($paymentIntentId)) {
            return;
        }

        $donation = Donation::updateOrCreate(
            ['stripe_payment_intent_id' => $paymentIntentId],
            [
                'campaign_id' => $subscription->campaign_id,
                'donor_id' => $subscription->donor_id,
                'subscription_id' => $subscription->getKey(),
                'source' => $source,
                'gross_amount' => $amount,
                'base_amount' => strtolower($currency) === 'myr' ? $amount : null,
                'donor_fee_covered' => $feeCoverAmount,
                'currency' => strtolower($currency),
                'base_currency' => 'myr',
                'status' => DonationStatus::Succeeded,
                'type' => DonationType::Recurring,
                'stripe_invoice_id' => $invoice->id ?? null,
            ],
        );

        try {
            app(SyncDonationStripeDetails::class)->sync($donation, null, $stripeOptions);
            $donation->refresh();
        } catch (\Throwable $e) {
            report($e);
        }

        // Re-sync once the balance transaction has settled to capture the MYR base amount.
        SyncDonationStripeDetailsJob::dispatch($donation->getKey())->delay(now()->addMinutes(2));

        SendNewSubscriptionNotification::dispatch($donation)->delay(now()->addMinutes(5));
        SendDonorNewSubscriptionNotification::dispatch($donation);
    }

    private function syncPaymentMethod(Donor $donor, ?string $stripePaymentMethodId, array $stripeOptions): void
    {
        if (! $stripePaymentMethodId) {
            return;
        }

        try {
            $pm = PaymentMethod::retrieve($stripePaymentMethodId, $stripeOptions);

            if ($pm->type !== 'card' || ! $pm->card) {
                return;
            }

            // Refresh rather than skip: Stripe's card updater rewrites the
            // expiry on the same payment method when a bank reissues a card,
            // and the expiry notices read these columns, not Stripe.
            DonorPaymentMethod::updateOrCreate(
                ['stripe_payment_method_id' => $pm->id],
                [
                    'donor_id' => $donor->getKey(),
                    'brand' => ucfirst($pm->card->brand),
                    'last4' => $pm->card->last4,
                    'exp_month' => $pm->card->exp_month,
                    'exp_year' => $pm->card->exp_year,
                    'country' => $pm->card->country ?? null,
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function resolveOrCreateDonor(
        string $firstName,
        string $lastName,
        string $email,
    ): Donor {
        // Donors are keyed by their globally-unique email, matching the public donation form.
        return Donor::updateOrCreate(
            ['email' => str($email)->lower()->toString()],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
            ],
        );
    }
}
