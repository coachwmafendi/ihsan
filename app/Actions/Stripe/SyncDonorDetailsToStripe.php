<?php

declare(strict_types=1);

namespace App\Actions\Stripe;

use App\Enums\SubscriptionStatus;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\StripeMetadata;
use Illuminate\Database\Eloquent\Builder;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Throwable;

class SyncDonorDetailsToStripe
{
    public function sync(Donor $donor, Organization $organization): bool
    {
        $customerId = app(ResolveDonorStripeCustomer::class)->resolveWithoutCreating($donor, $organization);

        if (blank($customerId)) {
            return false;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $stripeOptions = $organization->stripeOptions();

        $successful = $this->updateStripeCustomer($donor, $customerId, $stripeOptions);

        // Do not attempt subscription updates if the customer update failed.
        if (! $successful) {
            return false;
        }

        $subscriptions = $donor->subscriptions()
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused])
            ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', $organization->getKey()))
            ->get();

        foreach ($subscriptions as $subscription) {
            if ($subscription->stripe_subscription_id === null) {
                continue;
            }

            if (! $this->updateStripeSubscription($subscription, $donor, $stripeOptions)) {
                $successful = false;
            }
        }

        return $successful;
    }

    /**
     * Update the Stripe Customer record for the donor.
     */
    private function updateStripeCustomer(Donor $donor, string $customerId, array $stripeOptions): bool
    {
        try {
            Customer::update($customerId, [
                'name' => $donor->name,
                'email' => $donor->email,
                'preferred_locales' => StripeMetadata::customerLocale($donor) ?? [],
            ], $stripeOptions);

            return true;
        } catch (Throwable $e) {
            report($e);
        }

        return false;
    }

    /**
     * Update a single Stripe Subscription with the donor's current metadata.
     */
    private function updateStripeSubscription(Subscription $subscription, Donor $donor, array $stripeOptions): bool
    {
        try {
            $currentSubscription = StripeSubscription::retrieve($subscription->stripe_subscription_id, $stripeOptions);

            StripeSubscription::update($subscription->stripe_subscription_id, [
                'metadata' => array_merge(
                    $currentSubscription->metadata?->toArray() ?? [],
                    StripeMetadata::forDonorUpdate($donor),
                ),
            ], $stripeOptions);

            return true;
        } catch (Throwable $e) {
            report($e);
        }

        return false;
    }
}
