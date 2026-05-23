<?php

namespace App\Jobs;

use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Enums\DonationStatus;
use App\Models\Donation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Stripe\Stripe;

class SyncDonationStripeDetailsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $donationId,
    ) {}

    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(SyncDonationStripeDetails $syncDonationStripeDetails): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $donation = Donation::query()
            ->with(['campaign.organization', 'donor'])
            ->find($this->donationId);

        if ($donation === null || $donation->status !== DonationStatus::Succeeded || blank($donation->stripe_payment_intent_id)) {
            return;
        }

        $organization = $donation->campaign?->organization;
        $stripeOptions = $organization?->stripe_account_id && $organization->stripe_onboarded
            ? ['stripe_account' => $organization->stripe_account_id]
            : [];

        $syncDonationStripeDetails->sync($donation, null, $stripeOptions);
    }
}
