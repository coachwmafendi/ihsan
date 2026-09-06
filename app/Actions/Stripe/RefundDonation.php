<?php

namespace App\Actions\Stripe;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Services\DonationActivityLogger;
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

        // Stripe leaves the application fee with the platform unless the refund
        // asks for it back, which would leave the organization paying our fee
        // on a donation that no longer exists. Partial refunds return a
        // proportional share of the fee.
        Refund::create([
            'charge' => $donation->stripe_charge_id,
            'refund_application_fee' => true,
        ], $stripeOptions);

        $donation->update([
            'status' => DonationStatus::Refunded,
            'refunded_at' => now(),
        ]);

        DonationActivityLogger::refunded(
            $donation,
            (float) $donation->gross_amount,
            auth()->user(),
            ['source' => 'manual']
        );
    }
}
