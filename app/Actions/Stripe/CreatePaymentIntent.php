<?php

namespace App\Actions\Stripe;

use App\Models\Donation;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class CreatePaymentIntent
{
    public function create(Donation $donation): PaymentIntent
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $organization = $donation->campaign->organization;

        $params = [
            'amount' => (int) ((float) $donation->gross_amount * 100),
            'currency' => strtolower($donation->currency),
            'metadata' => [
                'donation_id' => (string) $donation->getKey(),
                'campaign_id' => (string) $donation->campaign_id,
                'organization_id' => (string) $organization->getKey(),
            ],
        ];

        if ($organization->stripe_account_id && $organization->stripe_onboarded) {
            $params['application_fee_amount'] = (int) ((float) $donation->gross_amount * 0.05 * 100);
            $params['transfer_data'] = [
                'destination' => $organization->stripe_account_id,
            ];
        }

        return PaymentIntent::create($params);
    }
}
