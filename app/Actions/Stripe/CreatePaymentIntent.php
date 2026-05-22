<?php

namespace App\Actions\Stripe;

use App\Enums\DonationType;
use App\Models\Donation;
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

        $params = [
            'amount' => (int) ((float) $donation->gross_amount * 100),
            'currency' => strtolower($donation->currency),
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
            'receipt_email' => $donation->donor?->email,
            'description' => (string) str($donation->campaign->title)->limit(200),
            'metadata' => [
                'donation_id' => (string) $donation->getKey(),
                'donor_name' => $donation->donor?->name ?? '',
                'donor_email' => $donation->donor?->email ?? '',
                'donor_phone' => $donation->donor?->phone ?? '',
                'campaign_id' => (string) $donation->campaign_id,
                'organization_id' => (string) $organization->getKey(),
            ],
        ];

        if ($donation->type === DonationType::Recurring) {
            $params['setup_future_usage'] = 'off_session';
        }

        if ($organization->stripe_account_id && $organization->stripe_onboarded) {
            $stripeOptions = ['stripe_account' => $organization->stripe_account_id];
            $customerParams = [
                'email' => $donation->donor?->email,
                'name' => $donation->donor?->name,
                'metadata' => [
                    'donor_id' => (string) $donation->donor_id,
                    'organization_id' => (string) $organization->getKey(),
                ],
            ];

            if (filled($donation->donor?->phone)) {
                $customerParams['phone'] = $donation->donor->phone;
            }

            $customer = Customer::create($customerParams, $stripeOptions);

            $params['customer'] = $customer->id;
            $params['application_fee_amount'] = (int) ((float) $donation->gross_amount * 0.05 * 100);

            return PaymentIntent::create($params, $stripeOptions);
        }

        return PaymentIntent::create($params);
    }
}
