<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Stripe\Price;
use Stripe\SetupIntent;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\SubscriptionItem;

class ManageStripeSubscription
{
    public function cancel(Subscription $subscription, bool $immediately = false): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing('campaign.organization');
        $stripeOptions = $this->stripeOptions($subscription);

        if ($immediately) {
            $stripeSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id, $stripeOptions);
            $stripeSubscription->cancel([], $stripeOptions);

            $subscription->update([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);
        } else {
            StripeSubscription::update($subscription->stripe_subscription_id, [
                'cancel_at_period_end' => true,
            ], $stripeOptions);

            $subscription->update([
                'cancel_at_period_end' => true,
            ]);
        }
    }

    public function pause(Subscription $subscription): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing('campaign.organization');
        $stripeOptions = $this->stripeOptions($subscription);

        StripeSubscription::update($subscription->stripe_subscription_id, [
            'pause_collection' => [
                'behavior' => 'mark_uncollectible',
            ],
        ], $stripeOptions);

        $subscription->update([
            'paused_until' => now()->addMonth(),
        ]);
    }

    public function resume(Subscription $subscription): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing('campaign.organization');
        $stripeOptions = $this->stripeOptions($subscription);

        StripeSubscription::update($subscription->stripe_subscription_id, [
            'pause_collection' => '',
        ], $stripeOptions);

        $subscription->update([
            'paused_until' => null,
        ]);
    }

    public function changeAmount(Subscription $subscription, float $newAmount, ?string $interval = null): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing(['campaign.organization', 'donor']);
        $organization = $subscription->campaign->organization;
        $stripeOptions = $this->stripeOptions($subscription);

        $stripeSubscription = StripeSubscription::retrieve([
            'id' => $subscription->stripe_subscription_id,
            'expand' => ['items.data.price.product'],
        ], $stripeOptions);

        $subscriptionItem = $stripeSubscription->items->data[0] ?? null;
        if ($subscriptionItem === null) {
            throw new \RuntimeException('No subscription item found.');
        }

        $product = $subscriptionItem->price->product;
        $productId = is_string($product) ? $product : $product->id;

        $effectiveInterval = $interval ?? $subscription->interval->value;

        $price = Price::create([
            'product' => $productId,
            'unit_amount' => (int) ($newAmount * 100),
            'currency' => strtolower($subscription->currency ?? 'myr'),
            'recurring' => ['interval' => $this->stripeInterval($effectiveInterval)],
        ], $stripeOptions);

        SubscriptionItem::update($subscriptionItem->id, [
            'price' => $price->id,
            'proration_behavior' => 'create_prorations',
        ], $stripeOptions);

        $subscription->update([
            'amount' => $newAmount,
            'stripe_price_id' => $price->id,
        ]);
    }

    public function createSetupIntent(Subscription $subscription): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing('campaign.organization');
        $stripeOptions = $this->stripeOptions($subscription);

        $stripeSubscription = StripeSubscription::retrieve(
            $subscription->stripe_subscription_id,
            $stripeOptions,
        );

        $setupIntent = SetupIntent::create([
            'customer' => $stripeSubscription->customer,
            'usage' => 'off_session',
        ], $stripeOptions);

        return $setupIntent->client_secret;
    }

    public function updatePaymentMethod(Subscription $subscription, string $paymentMethodId): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing('campaign.organization');
        $stripeOptions = $this->stripeOptions($subscription);

        StripeSubscription::update($subscription->stripe_subscription_id, [
            'default_payment_method' => $paymentMethodId,
        ], $stripeOptions);

        $donor = $subscription->donor;
        if ($donor && $donor->stripe_customer_id === null) {
            $stripeSubscription = StripeSubscription::retrieve(
                $subscription->stripe_subscription_id,
                $stripeOptions,
            );
            $donor->update(['stripe_customer_id' => $stripeSubscription->customer]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function stripeInterval(string $interval): string
    {
        return match ($interval) {
            'weekly' => 'week',
            'yearly' => 'year',
            default => 'month',
        };
    }

    private function stripeOptions(Subscription $subscription): array
    {
        $organization = $subscription->campaign->organization;

        if ($organization?->stripe_account_id && $organization->stripe_onboarded) {
            return ['stripe_account' => $organization->stripe_account_id];
        }

        return [];
    }
}
