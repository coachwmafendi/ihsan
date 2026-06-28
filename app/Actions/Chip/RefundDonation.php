<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Jobs\SendDonorRefundNotification;
use App\Jobs\SendRefundNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\ChipApi;
use Illuminate\Support\Facades\DB;

class RefundDonation
{
    public function __construct(private ChipApi $chipApi) {}

    public function handle(Donation $donation): void
    {
        if (! filled($donation->chip_purchase_id)) {
            throw new \RuntimeException('No CHIP purchase ID found for this donation.');
        }

        $donation->loadMissing('campaign.organization');

        $organization = $donation->campaign?->organization;

        if ($organization === null || ! $organization->chipOnboarded()) {
            throw new \RuntimeException('CHIP is not configured for this organization.');
        }

        $this->chipApi->refundPurchase((string) $donation->chip_purchase_id, $organization);

        $refundAmount = (float) ($donation->base_amount ?? $donation->gross_amount);

        DB::transaction(function () use ($donation, $refundAmount): void {
            $lockedDonation = Donation::query()->whereKey($donation->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedDonation->status !== DonationStatus::Refunded) {
                $lockedDonation->update([
                    'status' => DonationStatus::Refunded,
                    'refunded_at' => now(),
                ]);
            }

            $campaign = Campaign::query()->whereKey($lockedDonation->campaign_id)->lockForUpdate()->first();

            if ($campaign !== null) {
                $newCollected = max(0, (float) $campaign->collected_amount - $refundAmount);
                $campaign->update(['collected_amount' => $newCollected]);
            }
        });

        SendRefundNotification::dispatch($donation);
        SendDonorRefundNotification::dispatch($donation);
    }
}
