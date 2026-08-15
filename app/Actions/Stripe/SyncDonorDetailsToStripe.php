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

        try {
            Customer::update($donor->stripe_customer_id, [
                'name' => trim(($donor->first_name ?? '').' '.($donor->last_name ?? '')),
                'email' => $donor->email,
                'preferred_locales' => StripeMetadata::customerLocale($donor) ?? [],
            ], $stripeOptions);

            $donor->subscriptions()
                ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused])
                ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', $organization->getKey()))
                ->each(function (Subscription $subscription) use ($donor, $stripeOptions): void {
                    if ($subscription->stripe_subscription_id === null) {
                        return;
                    }

                    StripeSubscription::update($subscription->stripe_subscription_id, [
                        'metadata' => StripeMetadata::forDonorUpdate($donor),
                    ], $stripeOptions);
                });

            return true;
        } catch (Throwable $e) {
            report($e);

            return false;
        }
    }
}
