<?php

declare(strict_types=1);

namespace App\Actions\Stripe;

use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\StripeMetadata;
use Stripe\Customer;
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

        $stripeOptions = $organization !== null && $organization->stripe_active
            ? ['stripe_account' => $organization->stripe_account_id]
            : [];

        $customerId = $this->ensureStripeCustomer($donor, $organization, $stripeOptions);

        $paymentMethod = StripePaymentMethod::retrieve($paymentMethodId, $stripeOptions);
        $paymentMethod->attach(['customer' => $customerId], $stripeOptions);

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

    /**
     * @param  array<string, string>  $stripeOptions
     */
    private function ensureStripeCustomer(?Donor $donor, ?Organization $organization, array $stripeOptions): string
    {
        if (! $donor instanceof Donor) {
            throw new \RuntimeException('Subscription is not linked to a donor.');
        }

        if (filled($donor->stripe_customer_id)) {
            return $donor->stripe_customer_id;
        }

        if ($organization === null || blank($donor->email)) {
            throw new \RuntimeException('Donor does not have a Stripe customer ID.');
        }

        $customerParams = [
            'email' => $donor->email,
            'metadata' => StripeMetadata::forDonorCustomer(
                donor: $donor,
                organization: $organization,
                source: 'donor_portal_payment_method_update',
            ),
        ];

        $customerName = trim(($donor->first_name ?? '').' '.($donor->last_name ?? ''));

        if ($customerName !== '') {
            $customerParams['name'] = $customerName;
        }

        if (filled($donor->phone)) {
            $customerParams['phone'] = $donor->phone;
        }

        $address = StripeMetadata::customerAddress($donor);
        if ($address !== null) {
            $customerParams['address'] = $address;
        }

        $locale = StripeMetadata::customerLocale($donor);
        if ($locale !== null) {
            $customerParams['preferred_locales'] = $locale;
        }

        $customer = Customer::create($customerParams, $stripeOptions);

        $donor->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }
}
