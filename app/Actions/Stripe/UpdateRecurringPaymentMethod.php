<?php

namespace App\Actions\Stripe;

use App\Models\DonorPaymentMethod;
use App\Models\Subscription;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class UpdateRecurringPaymentMethod
{
    public function update(Subscription $subscription, string $paymentMethodId): void
    {
        $subscription->loadMissing('campaign.organization', 'donor');
        $organization = $subscription->campaign?->organization;
        $donor = $subscription->donor;

        if ($organization === null || $donor === null) {
            throw new \RuntimeException('Missing Stripe customer.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));
        $stripeOptions = $organization->stripeOptions();

        $customerId = app(ResolveDonorStripeCustomer::class)->resolve($donor, $organization);

        $paymentMethod = PaymentMethod::retrieve($paymentMethodId, $stripeOptions);
        $paymentMethod->attach(['customer' => $customerId], $stripeOptions);

        if ($paymentMethod->type !== 'card' || $paymentMethod->card === null) {
            throw new \RuntimeException('Only card payment methods supported.');
        }

        DonorPaymentMethod::query()->where('donor_id', $donor->getKey())->update(['is_default' => false]);

        $donorPaymentMethod = DonorPaymentMethod::updateOrCreate(
            [
                'donor_id' => $donor->getKey(),
                'stripe_payment_method_id' => $paymentMethodId,
            ],
            [
                'brand' => ucfirst((string) $paymentMethod->card->brand),
                'last4' => $paymentMethod->card->last4,
                'exp_month' => $paymentMethod->card->exp_month,
                'exp_year' => $paymentMethod->card->exp_year,
                'country' => $paymentMethod->card->country ?? null,
                'is_default' => true,
            ],
        );

        $subscription->update(['donor_payment_method_id' => $donorPaymentMethod->getKey()]);
    }
}
