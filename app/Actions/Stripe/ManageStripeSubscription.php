<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use Stripe\Price;
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

    public function changeAmount(Subscription $subscription, float $newAmount): void
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

        $price = Price::create([
            'product' => $productId,
            'unit_amount' => (int) ($newAmount * 100),
            'currency' => strtolower($subscription->currency ?? 'myr'),
            'recurring' => ['interval' => 'month'],
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

    /**
     * @return array<string, string>
     */
    private function stripeOptions(Subscription $subscription): array
    {
        $organization = $subscription->campaign->organization;

        if ($organization?->stripe_account_id && $organization->stripe_onboarded) {
            return ['stripe_account' => $organization->stripe_account_id];
        }

        return [];
    }
}
