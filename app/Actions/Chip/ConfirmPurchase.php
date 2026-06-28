<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendCampaignMilestoneNotification;
use App\Jobs\SendDonationReceipt;
use App\Jobs\SendDonorNewSubscriptionNotification;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendLinkedInConversionEvent;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SendNewSubscriptionNotification;
use App\Jobs\SendSnapchatConversionEvent;
use App\Jobs\SendXAdsConversionEvent;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Subscription;
use App\Services\ChipApi;
use App\Services\SubscriptionSchedule;
use Carbon\CarbonImmutable;
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

        $isNewSubscription = $donation->type === DonationType::Recurring
            && $donation->fresh()?->subscription_id === null;

        if ($isNewSubscription) {
            $subscription = $this->createRecurringPlan($donation, $purchase);

            if ($subscription->wasRecentlyCreated) {
                SendNewSubscriptionNotification::dispatch($donation);
                SendDonorNewSubscriptionNotification::dispatch($donation);
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $purchase
     */
    private function createRecurringPlan(Donation $donation, array $purchase): Subscription
    {
        $token = $purchase['id'] ?? $donation->chip_purchase_id;

        $subscription = DB::transaction(function () use ($donation, $token): Subscription {
            $nextChargeAt = SubscriptionSchedule::nextChargeAt(CarbonImmutable::now(), SubscriptionInterval::Monthly);

            $subscription = Subscription::query()->create([
                'campaign_id' => $donation->campaign_id,
                'donor_id' => $donation->donor_id,
                'amount' => $donation->gross_amount,
                'currency' => (string) $donation->currency,
                'interval' => SubscriptionInterval::Monthly,
                'status' => SubscriptionStatus::Active,
                'payment_count' => 1,
                'current_period_start' => now(),
                'current_period_end' => $nextChargeAt,
                'next_charge_at' => $nextChargeAt,
                'last_charge_at' => now(),
                'cover_fee' => false,
                'source' => $donation->source ?? 'checkout_modal',
                'chip_recurring_token' => $token,
            ]);

            $donation->update(['subscription_id' => $subscription->getKey()]);

            return $subscription;
        });

        return $subscription;
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
