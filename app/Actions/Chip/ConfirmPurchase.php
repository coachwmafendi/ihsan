<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\SendCampaignMilestoneNotification;
use App\Jobs\SendDonationReceipt;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendLinkedInConversionEvent;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SendSnapchatConversionEvent;
use App\Jobs\SendXAdsConversionEvent;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\ChipApi;
use Illuminate\Support\Facades\DB;

class ConfirmPurchase
{
    public function __construct(
        private ChipApi $chipApi,
    ) {}

    public function handle(Donation $donation): bool
    {
        if ($donation->status === DonationStatus::Succeeded) {
            return true;
        }

        $organization = $donation->campaign?->organization;

        if ($organization === null) {
            return false;
        }

        $purchase = $this->chipApi->getPurchase((string) $donation->chip_purchase_id, $organization);

        if (($purchase['status'] ?? '') !== 'paid') {
            return false;
        }

        $this->applyPurchaseDetails($donation, $purchase);

        $finalizeResult = DB::transaction(function () use ($donation): array {
            $lockedDonation = Donation::query()->whereKey($donation->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedDonation->status === DonationStatus::Succeeded) {
                return ['wasAlreadySucceeded' => true];
            }

            $lockedDonation->update([
                'status' => DonationStatus::Succeeded,
            ]);

            $campaign = Campaign::query()->whereKey($lockedDonation->campaign_id)->lockForUpdate()->first();

            if ($campaign === null) {
                return ['wasAlreadySucceeded' => false, 'campaign' => null, 'previousCollected' => 0];
            }

            $previousCollected = (float) $campaign->collected_amount;
            $campaign->increment('collected_amount', (float) ($lockedDonation->base_amount ?? $lockedDonation->gross_amount));

            return ['wasAlreadySucceeded' => false, 'campaign' => $campaign, 'previousCollected' => $previousCollected];
        });

        if ($finalizeResult['wasAlreadySucceeded']) {
            return true;
        }

        $campaign = $finalizeResult['campaign'];

        if ($campaign === null) {
            return true;
        }

        $previousCollected = $finalizeResult['previousCollected'];
        $campaign->refresh();

        SendCampaignMilestoneNotification::dispatch($campaign, $previousCollected);

        SendDonationReceipt::dispatch($donation);

        if ($donation->type !== DonationType::Recurring) {
            SendNewDonationNotification::dispatch($donation);
        }

        SendLargeDonationNotification::dispatch($donation);
        SendMetaConversionEvent::dispatch($donation);
        SendLinkedInConversionEvent::dispatch($donation);
        SendXAdsConversionEvent::dispatch($donation);
        SendSnapchatConversionEvent::dispatch($donation);

        return true;
    }

    /**
     * @param  array<string, mixed>  $purchase
     */
    private function applyPurchaseDetails(Donation $donation, array $purchase): void
    {
        $payment = is_array($purchase['payment'] ?? null) ? $purchase['payment'] : null;

        if ($payment === null) {
            return;
        }

        $feeAmount = $payment['fee_amount'] ?? 0;
        $netAmount = $payment['net_amount'] ?? 0;

        $donation->update([
            'stripe_fee' => ((float) $feeAmount) / 100,
            'net_amount' => ((float) $netAmount) / 100,
        ]);
    }
}
