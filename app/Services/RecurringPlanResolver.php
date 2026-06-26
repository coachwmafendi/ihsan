<?php

namespace App\Services;

use App\Actions\Stripe\CreateAppControlledRecurringPlan;
use App\Actions\Stripe\CreateRecurringSubscription;
use App\Models\Donation;
use App\Models\Subscription;
use Stripe\PaymentIntent as StripePaymentIntent;

class RecurringPlanResolver
{
    /**
     * @param  array<string, string>  $stripeOptions
     */
    public function create(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions = []): Subscription
    {
        if (config('services.recurring.use_app_controlled', true)) {
            return app(CreateAppControlledRecurringPlan::class)->create($donation, $paymentIntent, $stripeOptions);
        }

        return app(CreateRecurringSubscription::class)->create($donation, $paymentIntent, $stripeOptions);
    }
}
