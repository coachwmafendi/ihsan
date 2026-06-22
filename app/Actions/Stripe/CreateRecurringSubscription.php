<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Subscription;
use App\Services\StripeMetadata;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
        $lock = Cache::lock('create-recurring-subscription.'.$donation->getKey(), 60);

        try {
            $lock->block(30);
        } catch (LockTimeoutException) {
            $existingSubscription = $donation->fresh()?->subscription;

            if ($existingSubscription instanceof Subscription) {
                return $existingSubscription;
            }

            throw new \RuntimeException('Unable to acquire subscription creation lock for donation '.$donation->getKey());
        }

        try {
            return $this->createLocked($donation, $paymentIntent, $stripeOptions);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, string>  $stripeOptions
     */
    private function createLocked(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions = []): Subscription
    {
        $existingSubscription = $donation->fresh()?->subscription;

        if ($existingSubscription instanceof Subscription) {
            return $existingSubscription;
        }

        $donation->loadMissing('campaign.organization');
        $organization = $donation->campaign?->organization;

        $currentPeriodStart = now();
        $currentPeriodEnd = $currentPeriodStart->copy()->addMonth();

        $paymentMethodId = is_string($paymentIntent->payment_method ?? null)
            ? $paymentIntent->payment_method
            : ($paymentIntent->payment_method->id ?? null);
        $customerId = is_string($paymentIntent->customer ?? null)
            ? $paymentIntent->customer
            : ($paymentIntent->customer->id ?? null);

        if ($paymentMethodId !== null) {
            $customerId ??= $this->resolveCustomerId($donation, $paymentIntent, $paymentMethodId, $stripeOptions);

            [$stripeSubscriptionId, $stripePriceId] = $customerId !== null
                ? $this->createStripeSubscription(
                    donation: $donation,
                    customerId: $customerId,
                    paymentMethodId: $paymentMethodId,
                    currentPeriodEnd: $currentPeriodEnd,
                    stripeOptions: $stripeOptions,
                )
                : [null, null];
        } else {
            [$stripeSubscriptionId, $stripePriceId] = [null, null];
        }

        $subscription = DB::transaction(function () use (
            $donation,
            $stripeSubscriptionId,
            $stripePriceId,
            $currentPeriodStart,
            $currentPeriodEnd
        ): Subscription {
            $freshDonation = Donation::query()->whereKey($donation->getKey())->lockForUpdate()->firstOrFail();
            $freshDonation->load('subscription');

            if ($freshDonation->subscription !== null) {
                return $freshDonation->subscription;
            }

            $newSubscription = Subscription::query()->create([
                'campaign_id' => $freshDonation->campaign_id,
                'donor_id' => $freshDonation->donor_id,
                'source' => $freshDonation->source,
                'stripe_subscription_id' => $stripeSubscriptionId,
                'stripe_price_id' => $stripePriceId,
                'amount' => (float) $freshDonation->gross_amount,
                'currency' => $freshDonation->currency,
                'interval' => SubscriptionInterval::Monthly,
                'status' => SubscriptionStatus::Active,
                'cover_fee' => (float) ($freshDonation->donor_fee_covered ?? 0) > 0,
                'fee_cover_amount' => (float) ($freshDonation->donor_fee_covered ?? 0) > 0 ? (float) $freshDonation->donor_fee_covered : null,
                'current_period_start' => $currentPeriodStart,
                'current_period_end' => $currentPeriodEnd,
                'payment_count' => 1,
            ]);

            $freshDonation->update(['subscription_id' => $newSubscription->getKey()]);

            return $newSubscription;
        });

        if ($stripeSubscriptionId !== null && $organization !== null) {
            StripeSubscription::update($stripeSubscriptionId, [
                'metadata' => StripeMetadata::forRecurringSubscription(
                    donation: $donation,
                    organization: $organization,
                    element: $donation->element,
                    subscription: $subscription,
                ),
            ], $stripeOptions);
        }

        return $subscription;
    }

    /**
     * @param  array<string, string>  $stripeOptions
     */
    private function resolveCustomerId(Donation $donation, StripePaymentIntent $paymentIntent, string $paymentMethodId, array $stripeOptions): ?string
    {
        $donorEmail = $paymentIntent->metadata->{StripeMetadata::key('donor_email')}
            ?? $paymentIntent->metadata->donor_email
            ?? $donation->donor?->email;

        if ($donorEmail === null) {
            return null;
        }

        $donation->loadMissing('campaign.organization');
        $organization = $donation->campaign?->organization;

        $customerParams = [
            'email' => $donorEmail,
            'name' => $donation->donor?->name,
            'metadata' => $organization !== null
                ? StripeMetadata::forDonorCustomer(
                    donor: $donation->donor,
                    organization: $organization,
                    source: 'recurring_subscription',
                )
                : [],
        ];

        if (filled($donation->donor?->phone)) {
            $customerParams['phone'] = $donation->donor->phone;
        }

        $address = StripeMetadata::customerAddress($donation->donor);
        if ($address !== null) {
            $customerParams['address'] = $address;
        }

        $locale = StripeMetadata::customerLocale($donation->donor);
        if ($locale !== null) {
            $customerParams['preferred_locales'] = $locale;
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
        $donation->loadMissing('campaign.organization');
        $organization = $donation->campaign?->organization;

        $productMetadata = $organization !== null && $donation->campaign !== null
            ? StripeMetadata::forProduct($donation->campaign, $organization, 'donation')
            : [];

        $product = StripeProduct::create([
            'name' => $donation->campaign?->title ?? 'Donation',
            'metadata' => $productMetadata,
        ], $stripeOptions);

        $price = StripePrice::create([
            'product' => $product->id,
            'currency' => $donation->currency,
            'unit_amount' => (int) ((float) $donation->gross_amount * 100),
            'recurring' => ['interval' => 'month'],
            'metadata' => $organization !== null && $donation->campaign !== null
                ? StripeMetadata::forPrice($donation->campaign, $organization, (float) $donation->gross_amount, $donation->currency, 'month', 'donation')
                : [],
        ], $stripeOptions);

        $items = [['price' => $price->id]];

        $feeCoverAmount = (float) ($donation->donor_fee_covered ?? 0);
        if ($feeCoverAmount > 0) {
            $feeProduct = StripeProduct::create([
                'name' => ($donation->campaign?->title ?? 'Donation').' - Processing fee cover',
                'metadata' => $organization !== null && $donation->campaign !== null
                    ? StripeMetadata::forProduct($donation->campaign, $organization, 'fee_cover')
                    : [],
            ], $stripeOptions);

            $feePrice = StripePrice::create([
                'product' => $feeProduct->id,
                'currency' => $donation->currency,
                'unit_amount' => (int) ($feeCoverAmount * 100),
                'recurring' => ['interval' => 'month'],
                'metadata' => $organization !== null && $donation->campaign !== null
                    ? StripeMetadata::forPrice($donation->campaign, $organization, $feeCoverAmount, $donation->currency, 'month', 'fee_cover')
                    : [],
            ], $stripeOptions);

            $items[] = ['price' => $feePrice->id];
        }

        $params = [
            'customer' => $customerId,
            'items' => $items,
            'default_payment_method' => $paymentMethodId,
            'trial_end' => $currentPeriodEnd->getTimestamp(),
            'metadata' => $organization !== null
                ? StripeMetadata::forRecurringSubscription(
                    donation: $donation,
                    organization: $organization,
                    element: $donation->element,
                )
                : [],
        ];

        $subscription = StripeSubscription::create($params, $stripeOptions);

        return [$subscription->id, $price->id];
    }
}
