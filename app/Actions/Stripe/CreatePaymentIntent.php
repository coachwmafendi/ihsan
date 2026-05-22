<?php

namespace App\Actions\Stripe;

use App\Enums\DonationType;
use App\Models\Donation;
use Stripe\Account;
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
            'metadata' => [
                'donation_id' => (string) $donation->getKey(),
                'donor_email' => $donation->donor?->email ?? '',
                'campaign_id' => (string) $donation->campaign_id,
                'organization_id' => (string) $organization->getKey(),
            ],
        ];

        if ($donation->type === DonationType::Recurring) {
            $params['setup_future_usage'] = 'off_session';
        }

        if ($organization->stripe_account_id && $organization->stripe_onboarded) {
            $platformAccount = Account::retrieve();

            if ($organization->stripe_account_id !== $platformAccount->id) {
                $params['application_fee_amount'] = (int) ((float) $donation->gross_amount * 0.05 * 100);
                $params['transfer_data'] = [
                    'destination' => $organization->stripe_account_id,
                ];
            }
        }

        return PaymentIntent::create($params);
    }
}
