<?php

namespace App\Actions\Stripe;

use App\Models\Donation;
use Stripe\Refund;
use Stripe\Stripe;

class RefundDonation
{
    public function handle(Donation $donation): void
    {
        if (! $donation->stripe_charge_id) {
            throw new \RuntimeException('No Stripe charge ID found for this donation.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $donation->loadMissing('campaign.organization');

        $stripeOptions = [];
        $organization = $donation->campaign?->organization;
        if ($organization?->stripe_account_id && $organization->stripe_onboarded) {
            $stripeOptions = ['stripe_account' => $organization->stripe_account_id];
        }

        Refund::create([
            'charge' => $donation->stripe_charge_id,
        ], $stripeOptions);
    }
}
