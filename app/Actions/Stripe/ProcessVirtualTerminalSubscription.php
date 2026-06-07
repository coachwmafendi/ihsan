<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
use App\Models\Subscription;
use Stripe\Customer;
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
        ?string $savedCardId = null,
        ?string $paymentMethodId = null,
        string $source = 'virtual_terminal',
    ): Subscription {
        Stripe::setApiKey(config('services.stripe.secret'));

        $campaign = Campaign::query()
            ->where('id', $campaignId)
            ->where('organization_id', $organization->getKey())
            ->firstOrFail();

        $donor = $this->resolveOrCreateDonor($firstName, $lastName, $email, $organization);

        $stripeOptions = $organization->stripe_account_id
            ? ['stripe_account' => $organization->stripe_account_id]
            : [];

        try {
            if (! $donor->stripe_customer_id) {
                $customer = Customer::create([
                    'name' => $donor->name,
                    'email' => $donor->email,
                    'metadata' => [
                        'donor_id' => (string) $donor->getKey(),
                        'organization_id' => (string) $organization->getKey(),
                    ],
                ], $stripeOptions);

                $donor->update(['stripe_customer_id' => $customer->id]);
            }

            $unitAmount = (int) ($amount * 100);

            $existingPrices = Price::all([
                'product' => $campaign->stripe_product_id,
                'unit_amount' => $unitAmount,
                'currency' => 'myr',
            ], $stripeOptions);

            $price = $existingPrices->data[0] ?? null;

            if ($price && $price->recurring->interval !== 'month') {
                $price = null;
            }

            if (! $price) {
                $price = Price::create([
                    'unit_amount' => $unitAmount,
                    'currency' => 'myr',
                    'recurring' => ['interval' => 'month'],
                    'product' => $campaign->stripe_product_id,
                ], $stripeOptions);
            }

            if ($paymentMethodId) {
                $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $stripeOptions);
                $paymentMethod->attach(['customer' => $donor->stripe_customer_id], $stripeOptions);
            }

            $subscriptionParams = [
                'customer' => $donor->stripe_customer_id,
                'items' => [['price' => $price->id]],
                'metadata' => [
                    'campaign_id' => (string) $campaign->getKey(),
                    'donor_public_id' => $donor->public_id,
                    'source' => 'virtual_terminal',
                    'organization_id' => (string) $organization->getKey(),
                ],
            ];

            if ($savedCardId) {
                $subscriptionParams['default_payment_method'] = $savedCardId;
            } elseif ($paymentMethodId) {
                $subscriptionParams['default_payment_method'] = $paymentMethodId;
            }

            if ($organization->stripe_account_id && $organization->fee_collection_method === 'upfront') {
                $feePercent = (float) config('services.stripe.processing_fee_percent', 2.5);
                $subscriptionParams['application_fee_percent'] = $feePercent;
            }

            $stripeSubscription = StripeSubscription::create($subscriptionParams, $stripeOptions);
        } catch (CardException $e) {
            throw new \RuntimeException('Card declined: '.$e->getMessage());
        } catch (InvalidRequestException $e) {
            throw new \RuntimeException('Invalid payment request. Please check your details.');
        } catch (ApiErrorException $e) {
            throw new \RuntimeException('Payment service error. Please try again.');
        }

        $subscription = Subscription::create([
            'campaign_id' => $campaign->getKey(),
            'donor_id' => $donor->getKey(),
            'source' => $source,
            'amount' => $amount,
            'currency' => 'myr',
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
            'stripe_subscription_id' => $stripeSubscription->id,
            'stripe_price_id' => $price->id,
            'started_at' => now(),
            'current_period_start' => now(),
        ]);

        // TODO: Send subscription confirmation email

        // Sync payment method details to local cache
        $pmId = $paymentMethodId ?? $savedCardId;
        $this->syncPaymentMethod($donor, $pmId, $stripeOptions);

        return $subscription;
    }

    private function syncPaymentMethod(Donor $donor, ?string $stripePaymentMethodId, array $stripeOptions): void
    {
        if (! $stripePaymentMethodId || ! $donor->stripe_customer_id) {
            return;
        }

        // Skip if already cached
        if (DonorPaymentMethod::where('stripe_payment_method_id', $stripePaymentMethodId)->exists()) {
            return;
        }

        try {
            $pm = PaymentMethod::retrieve($stripePaymentMethodId, $stripeOptions);

            if ($pm->type !== 'card' || ! $pm->card) {
                return;
            }

            DonorPaymentMethod::create([
                'donor_id' => $donor->getKey(),
                'stripe_payment_method_id' => $pm->id,
                'brand' => ucfirst($pm->card->brand),
                'last4' => $pm->card->last4,
                'exp_month' => $pm->card->exp_month,
                'exp_year' => $pm->card->exp_year,
                'country' => $pm->card->country ?? null,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function resolveOrCreateDonor(
        string $firstName,
        string $lastName,
        string $email,
        Organization $organization,
    ): Donor {
        $fullName = trim("{$firstName} {$lastName}");

        $donor = Donor::query()
            ->where('email', $email)
            ->whereHas('donations.campaign', fn ($q) => $q->where('organization_id', $organization->getKey()))
            ->first();

        if ($donor) {
            return $donor;
        }

        return Donor::create([
            'name' => $fullName,
            'email' => $email,
        ]);
    }
}
