<?php

namespace App\Actions\Stripe;

use App\Enums\DonationType;
use App\Models\Donation;
use App\Services\StripeMetadata;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class CreatePaymentIntent
{
    public function create(Donation $donation): PaymentIntent
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $donation->load(['campaign.organization', 'donor']);

        $organization = $donation->campaign->organization;

        $supportedCurrencies = ['myr', 'usd', 'sgd'];
        $currency = strtolower($donation->currency);

        if (! in_array($currency, $supportedCurrencies, true)) {
            throw new \InvalidArgumentException("Unsupported currency: {$currency}");
        }

        $orgSettings = $organization->settings ?? [];
        $acceptedCurrencies = $orgSettings['accepted_currencies'] ?? ['myr'];

        if (! in_array($currency, $acceptedCurrencies, true)) {
            throw new \InvalidArgumentException("Currency {$currency} is not accepted by this organization.");
        }

        $params = [
            'amount' => (int) (((float) $donation->gross_amount + (float) $donation->donor_fee_covered) * 100),
            'currency' => strtolower($donation->currency),
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'receipt_email' => $donation->donor?->email,
            'description' => (string) str($donation->campaign->title)->limit(200),
            'metadata' => StripeMetadata::forPaymentIntent(
                donation: $donation,
                organization: $organization,
                element: $donation->element,
            ),
        ];

        if ($donation->type === DonationType::Recurring) {
            $params['setup_future_usage'] = 'off_session';
        }

        if ($organization->stripe_account_id && $organization->stripe_onboarded) {
            $stripeOptions = ['stripe_account' => $organization->stripe_account_id];
            $customerName = trim(($donation->donor?->first_name ?? '').' '.($donation->donor?->last_name ?? ''));

            $customerParams = [
                'email' => $donation->donor?->email,
                'metadata' => StripeMetadata::forDonorCustomer(
                    donor: $donation->donor,
                    organization: $organization,
                    source: 'donation_checkout',
                ),
            ];

            if ($customerName !== '') {
                $customerParams['name'] = $customerName;
            }

            if (filled($donation->donor?->phone)) {
                $customerParams['phone'] = $donation->donor->phone;
            }

            $address = StripeMetadata::customerAddress($donation->donor);
            if ($address !== null) {
                $customerParams['address'] = $address;
            }

            $locale = StripeMetadata::customerLocale($donation->donor);
            if ($locale !== null) {
                $customerParams['preferred_locales'] = $locale;
            }

            if ($organization->fee_collection_method === 'upfront') {
                $feePercent = (float) config('services.stripe.processing_fee_percent', 2.5);
                $params['application_fee_amount'] = (int) round($params['amount'] * $feePercent / 100);
            }

            $params['metadata'][StripeMetadata::key('platform_fee_amount')] = (string) ($params['application_fee_amount'] ?? 0);

            $customer = Customer::create($customerParams, $stripeOptions);

            $params['customer'] = $customer->id;

            $donation->donor?->update(['stripe_customer_id' => $customer->id]);

            return PaymentIntent::create($params, $stripeOptions);
        }

        $params['metadata'][StripeMetadata::key('platform_fee_amount')] = (string) ($params['application_fee_amount'] ?? 0);

        return PaymentIntent::create($params);
    }
}
