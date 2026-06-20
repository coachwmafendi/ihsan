<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\StripeMetadata;
use Carbon\Carbon;
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

    public function pause(Subscription $subscription, int $months = 1): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing('campaign.organization');
        $stripeOptions = $this->stripeOptions($subscription);

        $resumeAt = $subscription->current_period_end
            ? $subscription->current_period_end->addMonths($months)
            : now()->addMonths($months);

        StripeSubscription::update($subscription->stripe_subscription_id, [
            'pause_collection' => [
                'behavior' => 'mark_uncollectible',
                'resumes_at' => $resumeAt->timestamp,
            ],
        ], $stripeOptions);

        $subscription->update([
            'paused_until' => $resumeAt,
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

        $campaign = $subscription->campaign;

        $price = Price::create([
            'product' => $productId,
            'unit_amount' => (int) ($newAmount * 100),
            'currency' => strtolower($subscription->currency ?? 'myr'),
            'recurring' => ['interval' => $this->stripeInterval($effectiveInterval)],
            'metadata' => $campaign !== null && $organization !== null
                ? StripeMetadata::forPrice($campaign, $organization, $newAmount, $subscription->currency ?? 'myr', $this->stripeInterval($effectiveInterval), 'donation')
                : [],
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
     * Update frequency, end date, billing day, and cover fee.
     *
     * @param  array{interval: string, billing_day: int, cancel_at: ?string, cover_fee: bool}  $data
     */
    public function updateDetails(Subscription $subscription, array $data): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing('campaign.organization');
        $organization = $subscription->campaign?->organization;
        $stripeOptions = $this->stripeOptions($subscription);

        $stripeParams = [];

        // Frequency change → new Stripe price with new interval
        $newInterval = SubscriptionInterval::from($data['interval']);
        if ($newInterval !== $subscription->interval) {
            $stripeSubscription = StripeSubscription::retrieve([
                'id' => $subscription->stripe_subscription_id,
                'expand' => ['items.data.price.product'],
            ], $stripeOptions);

            $subscriptionItem = $stripeSubscription->items->data[0] ?? null;
            if ($subscriptionItem) {
                $product = $subscriptionItem->price->product;
                $productId = is_string($product) ? $product : $product->id;

                $campaign = $subscription->campaign;
                $interval = $this->stripeInterval($data['interval']);

                $price = Price::create([
                    'product' => $productId,
                    'unit_amount' => (int) ($subscription->amount * 100),
                    'currency' => strtolower($subscription->currency ?? 'myr'),
                    'recurring' => ['interval' => $interval],
                    'metadata' => $campaign !== null && $organization !== null
                        ? StripeMetadata::forPrice($campaign, $organization, (float) $subscription->amount, $subscription->currency ?? 'myr', $interval, 'donation')
                        : [],
                ], $stripeOptions);

                SubscriptionItem::update($subscriptionItem->id, [
                    'price' => $price->id,
                    'proration_behavior' => 'none',
                ], $stripeOptions);

                $subscription->update([
                    'interval' => $newInterval,
                    'stripe_price_id' => $price->id,
                ]);
            }
        }

        // Billing day change → update billing_cycle_anchor
        $currentDay = (int) ($subscription->current_period_start ?? now())->format('j');
        $newDay = (int) $data['billing_day'];
        if ($newDay !== $currentDay && $newDay >= 1 && $newDay <= 28) {
            $anchor = Carbon::now()->setDay($newDay);
            if ($anchor->isPast()) {
                $anchor->addMonth();
            }

            $stripeParams['billing_cycle_anchor'] = $anchor->timestamp;
            $stripeParams['proration_behavior'] = 'none';
        }

        // End date → cancel_at
        $newCancelAt = $data['cancel_at'] ? Carbon::parse($data['cancel_at']) : null;
        $oldCancelAt = $subscription->cancel_at;
        if ($newCancelAt?->timestamp !== $oldCancelAt?->timestamp) {
            $stripeParams['cancel_at'] = $newCancelAt ? $newCancelAt->timestamp : '';
        }

        // Always re-fetch Stripe subscription to sync current_period_end
        $updated = StripeSubscription::retrieve($subscription->stripe_subscription_id, $stripeOptions);

        if (! empty($stripeParams)) {
            $updated = StripeSubscription::update($subscription->stripe_subscription_id, $stripeParams, $stripeOptions);
        }

        $updateData = [
            'cover_fee' => (bool) $data['cover_fee'],
            'cancel_at' => $newCancelAt,
        ];

        if ($updated->current_period_start) {
            $updateData['current_period_start'] = Carbon::createFromTimestamp($updated->current_period_start);
        }

        if ($updated->current_period_end) {
            $updateData['current_period_end'] = Carbon::createFromTimestamp($updated->current_period_end);
        }

        $subscription->update($updateData);
    }

    public function createSetupIntent(Subscription $subscription): string
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing('campaign.organization', 'donor');
        $stripeOptions = $this->stripeOptions($subscription);

        $customerId = $subscription->donor?->stripe_customer_id;

        if ($customerId === null) {
            $stripeSubscription = StripeSubscription::retrieve(
                $subscription->stripe_subscription_id,
                $stripeOptions,
            );
            $customerId = $stripeSubscription->customer;
            $subscription->donor?->update(['stripe_customer_id' => $customerId]);
        }

        $setupIntent = SetupIntent::create([
            'customer' => $customerId,
            'usage' => 'off_session',
            'payment_method_types' => ['card'],
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
