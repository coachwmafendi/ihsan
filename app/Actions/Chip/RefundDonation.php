<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Services\DonationActivityLogger;
use Chip\Exception\ChipApiException;
use InvalidArgumentException;
use RuntimeException;

final class RefundDonation
{
    /**
     * Refund a CHIP donation.
     *
     * @param  Donation  $donation  The donation to refund.
     */
    public function handle(Donation $donation): void
    {
        $donation->loadMissing('campaign.organization');

        if (blank($donation->chip_purchase_id)) {
            throw new RuntimeException('No CHIP purchase ID found for this donation.');
        }

        $organization = $donation->campaign?->organization;

        if ($organization === null) {
            throw new RuntimeException('Donation is not linked to an organization.');
        }

        try {
            $chip = ChipApiFactory::make($organization);
            $chip->purchases->refund($donation->chip_purchase_id);
        } catch (InvalidArgumentException $e) {
            report($e);

            throw new RuntimeException('Failed to initialize CHIP client: '.$e->getMessage(), previous: $e);
        } catch (ChipApiException $e) {
            report($e);

            throw new RuntimeException('Failed to refund CHIP donation: '.$e->getMessage(), previous: $e);
        }

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
