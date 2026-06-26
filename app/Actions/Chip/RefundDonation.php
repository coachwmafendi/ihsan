<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Models\Donation;
use Chip\Exception\ChipApiException;
use RuntimeException;

final class RefundDonation
{
    /**
     * Refund a CHIP donation.
     *
     * @param  Donation  $donation  The donation to refund.
     * @param  int|null  $amount  The amount to refund in the smallest currency unit (cents). Leave null for a full refund.
     */
    public function handle(Donation $donation, ?int $amount = null): void
    {
        $donation->loadMissing('campaign.organization');

        if (blank($donation->chip_purchase_id)) {
            throw new RuntimeException('No CHIP purchase ID found for this donation.');
        }

        $organization = $donation->campaign?->organization;

        if ($organization === null) {
            throw new RuntimeException('Donation is not linked to an organization.');
        }

        $chip = ChipApiFactory::make($organization);

        try {
            $chip->purchases->refund($donation->chip_purchase_id, $amount);
        } catch (ChipApiException $e) {
            report($e);

            throw new RuntimeException('Failed to refund CHIP donation: '.$e->getMessage(), previous: $e);
        }

        $donation->update([
            'status' => DonationStatus::Refunded,
            'refunded_at' => now(),
        ]);
    }
}
