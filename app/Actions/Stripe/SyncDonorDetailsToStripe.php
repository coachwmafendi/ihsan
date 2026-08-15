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
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

final class SyncDonorDetailsToStripe
{
    public function sync(Donor $donor, Organization $organization): bool
    {
        if (blank($donor->stripe_customer_id)) {
            return false;
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $stripeOptions = $organization->stripe_account_id
            ? ['stripe_account' => $organization->stripe_account_id]
            : [];

        $successful = $this->updateStripeCustomer($donor, $stripeOptions);

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
    private function updateStripeCustomer(Donor $donor, array $stripeOptions): bool
    {
        try {
            Customer::update($donor->stripe_customer_id, [
                'name' => $donor->name,
                'email' => $donor->email,
                'preferred_locales' => StripeMetadata::customerLocale($donor) ?? [],
            ], $stripeOptions);

            return true;
        } catch (ApiErrorException $e) {
            report($e);
        } catch (\Exception $e) {
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
            StripeSubscription::update($subscription->stripe_subscription_id, [
                'metadata' => StripeMetadata::forDonorUpdate($donor),
            ], $stripeOptions);

            return true;
        } catch (ApiErrorException $e) {
            report($e);
        } catch (\Exception $e) {
            report($e);
        }

        return false;
    }
}
