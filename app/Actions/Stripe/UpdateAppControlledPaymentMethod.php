<?php

declare(strict_types=1);

namespace App\Actions\Stripe;

use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Subscription;
use Stripe\PaymentMethod as StripePaymentMethod;
use Stripe\Stripe;

class UpdateAppControlledPaymentMethod
{
    public function update(Subscription $subscription, string $paymentMethodId): DonorPaymentMethod
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $subscription->loadMissing(['campaign.organization', 'donor']);
        $organization = $subscription->campaign?->organization;
        $donor = $subscription->donor;

        if (! $donor instanceof Donor || blank($donor->stripe_customer_id)) {
            throw new \RuntimeException('Donor does not have a Stripe customer ID.');
        }

        $stripeOptions = $organization !== null && filled($organization->stripe_account_id)
            ? ['stripe_account' => $organization->stripe_account_id]
            : [];

        $paymentMethod = StripePaymentMethod::retrieve($paymentMethodId, $stripeOptions);
        $paymentMethod->attach(['customer' => $donor->stripe_customer_id], $stripeOptions);

        $donorPaymentMethod = $donor->paymentMethods()->updateOrCreate(
            ['stripe_payment_method_id' => $paymentMethodId],
            [
                'brand' => $paymentMethod->card?->brand ?? 'Unknown',
                'last4' => $paymentMethod->card?->last4 ?? '',
                'exp_month' => $paymentMethod->card?->exp_month ?? null,
                'exp_year' => $paymentMethod->card?->exp_year ?? null,
                'country' => $paymentMethod->card?->country ?? null,
                'is_default' => true,
            ]
        );

        $subscription->update(['donor_payment_method_id' => $donorPaymentMethod->getKey()]);

        $donor->paymentMethods()->whereKeyNot($donorPaymentMethod->getKey())->update(['is_default' => false]);

        return $donorPaymentMethod;
    }
}
