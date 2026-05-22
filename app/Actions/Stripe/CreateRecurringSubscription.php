<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Subscription;
use Stripe\Customer as StripeCustomer;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Price as StripePrice;
use Stripe\Product as StripeProduct;
use Stripe\Subscription as StripeSubscription;

class CreateRecurringSubscription
{
    /**
     * @param  array<string, string>  $stripeOptions
     */
    public function create(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions = []): Subscription
    {
        $donation->loadMissing(['campaign', 'donor', 'subscription']);

        if ($donation->subscription !== null) {
            return $donation->subscription;
        }

        $currentPeriodStart = now();
        $currentPeriodEnd = $currentPeriodStart->copy()->addMonth();
        $stripeSubscriptionId = null;
        $stripePriceId = null;

        $paymentMethodId = is_string($paymentIntent->payment_method ?? null)
            ? $paymentIntent->payment_method
            : ($paymentIntent->payment_method->id ?? null);
        $customerId = is_string($paymentIntent->customer ?? null)
            ? $paymentIntent->customer
            : ($paymentIntent->customer->id ?? null);

        if ($paymentMethodId !== null) {
            $customerId ??= $this->resolveCustomerId($donation, $paymentIntent, $paymentMethodId, $stripeOptions);

            if ($customerId !== null) {
                [$stripeSubscriptionId, $stripePriceId] = $this->createStripeSubscription(
                    donation: $donation,
                    customerId: $customerId,
                    paymentMethodId: $paymentMethodId,
                    currentPeriodEnd: $currentPeriodEnd,
                    stripeOptions: $stripeOptions,
                );
            }
        }

        return Subscription::query()->create([
            'campaign_id' => $donation->campaign_id,
            'donor_id' => $donation->donor_id,
            'stripe_subscription_id' => $stripeSubscriptionId,
            'stripe_price_id' => $stripePriceId,
            'amount' => (float) $donation->gross_amount,
            'currency' => $donation->currency,
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
            'current_period_start' => $currentPeriodStart,
            'current_period_end' => $currentPeriodEnd,
        ]);
    }

    /**
     * @param  array<string, string>  $stripeOptions
     */
    private function resolveCustomerId(Donation $donation, StripePaymentIntent $paymentIntent, string $paymentMethodId, array $stripeOptions): ?string
    {
        $donorEmail = $paymentIntent->metadata->donor_email ?? $donation->donor?->email;

        if ($donorEmail === null) {
            return null;
        }

        $customerParams = [
            'email' => $donorEmail,
            'name' => $donation->donor?->name,
            'metadata' => [
                'donor_id' => (string) $donation->donor_id,
            ],
        ];

        if (filled($donation->donor?->phone)) {
            $customerParams['phone'] = $donation->donor->phone;
        }

        $customer = StripeCustomer::all(['email' => $donorEmail, 'limit' => 1], $stripeOptions)->first()
            ?? StripeCustomer::create($customerParams, $stripeOptions);

        StripePaymentIntent::update($paymentIntent->id, [
            'customer' => $customer->id,
        ], $stripeOptions);

        $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $stripeOptions);
        $paymentMethod->attach(['customer' => $customer->id], $stripeOptions);

        return $customer->id;
    }

    /**
     * @param  array<string, string>  $stripeOptions
     * @return array{0: string, 1: string}
     */
    private function createStripeSubscription(Donation $donation, string $customerId, string $paymentMethodId, \DateTimeInterface $currentPeriodEnd, array $stripeOptions): array
    {
        $product = StripeProduct::create([
            'name' => $donation->campaign?->title ?? 'Donation',
            'metadata' => ['campaign_id' => (string) $donation->campaign_id],
        ], $stripeOptions);

        $price = StripePrice::create([
            'product' => $product->id,
            'currency' => $donation->currency,
            'unit_amount' => (int) ((float) $donation->gross_amount * 100),
            'recurring' => ['interval' => 'month'],
        ], $stripeOptions);

        $params = [
            'customer' => $customerId,
            'items' => [['price' => $price->id]],
            'default_payment_method' => $paymentMethodId,
            'trial_end' => $currentPeriodEnd->getTimestamp(),
            'metadata' => [
                'campaign_id' => (string) $donation->campaign_id,
                'donor_id' => (string) $donation->donor_id,
                'donation_id' => (string) $donation->getKey(),
            ],
        ];

        if ($stripeOptions !== []) {
            $params['application_fee_percent'] = 5;
        }

        $subscription = StripeSubscription::create($params, $stripeOptions);

        return [$subscription->id, $price->id];
    }
}
