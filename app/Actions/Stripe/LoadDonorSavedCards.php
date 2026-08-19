<?php

namespace App\Actions\Stripe;

use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
use Stripe\PaymentMethod;
use Stripe\Stripe;

class LoadDonorSavedCards
{
    /**
     * @return array<int, array{id: string, brand: string, last4: string, exp_month: int, exp_year: int}>
     */
    public function handle(Donor $donor, Organization $organization): array
    {
        $customerId = app(ResolveDonorStripeCustomer::class)->resolveWithoutCreating($donor, $organization);

        if (blank($customerId)) {
            return [];
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $stripeOptions = $organization->stripeOptions();

        $paymentMethods = PaymentMethod::all([
            'customer' => $customerId,
            'type' => 'card',
        ], $stripeOptions);

        return collect($paymentMethods->data)
            ->map(function (PaymentMethod $paymentMethod, int $index) use ($donor): array {
                DonorPaymentMethod::updateOrCreate(
                    ['stripe_payment_method_id' => $paymentMethod->id],
                    [
                        'donor_id' => $donor->getKey(),
                        'brand' => ucfirst((string) $paymentMethod->card->brand),
                        'last4' => $paymentMethod->card->last4,
                        'exp_month' => $paymentMethod->card->exp_month,
                        'exp_year' => $paymentMethod->card->exp_year,
                        'country' => $paymentMethod->card->country ?? null,
                        'is_default' => $index === 0,
                    ],
                );

                return [
                    'id' => $paymentMethod->id,
                    'brand' => ucfirst((string) $paymentMethod->card->brand),
                    'last4' => $paymentMethod->card->last4,
                    'exp_month' => $paymentMethod->card->exp_month,
                    'exp_year' => $paymentMethod->card->exp_year,
                ];
            })
            ->all();
    }
}
