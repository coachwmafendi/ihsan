<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Data\ChargeResult;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionStatus;
use App\Jobs\SendCampaignMilestoneNotification;
use App\Jobs\SendDonorDunningNotification;
use App\Jobs\SendDonorRecurringPaymentNotification;
use App\Jobs\SendFailedPaymentNotification;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendLinkedInConversionEvent;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SendSnapchatConversionEvent;
use App\Jobs\SendXAdsConversionEvent;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use App\Services\ChipApi;
use App\Services\ScheduleRetry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Throwable;

class ChargeRecurringInstallment
{
    public function __construct(
        private ChipApi $chipApi,
        private ScheduleRetry $scheduleRetry,
    ) {}

    public function handle(Subscription $subscription): ChargeResult
    {
        $subscription->loadMissing(['campaign.organization', 'donor']);

        $guardResult = $this->guardSubscriptionState($subscription);
        if ($guardResult !== null) {
            return $guardResult;
        }

        $campaign = $subscription->campaign;
        $organization = $campaign?->organization;

        if ($campaign === null || $organization === null || ! $organization->chipOnboarded()) {
            $subscription->update(['status' => SubscriptionStatus::Failed, 'next_charge_at' => null]);

            return new ChargeResult('failed', errorCode: 'organization_not_onboarded');
        }

        $donor = $subscription->donor;
        if ($donor === null || blank($subscription->chip_recurring_token)) {
            $subscription->update(['status' => SubscriptionStatus::Failed, 'next_charge_at' => null]);

            return new ChargeResult('failed', errorCode: 'missing_recurring_token');
        }

        $grossAmount = (float) $subscription->amount;
        $feeCoverAmount = $subscription->cover_fee ? (float) ($subscription->fee_cover_amount ?? 0) : 0.0;
        $totalCents = (int) round(($grossAmount + $feeCoverAmount) * 100);
        $now = now();

        try {
            $purchase = $this->chipApi->createPurchase(
                $this->buildPseudoDonation($subscription, $totalCents),
                $this->returnUrl($subscription),
                $this->returnUrl($subscription),
            );

            $purchaseId = $purchase['id'] ?? null;

            if (blank($purchaseId)) {
                throw new \RuntimeException('CHIP renewal purchase creation did not return a purchase ID.');
            }

            $charged = $this->chipApi->chargePurchase($purchaseId, (string) $subscription->chip_recurring_token, $organization);
        } catch (Throwable $e) {
            report($e);
            $oldRetryCount = $this->recordAttemptFailure($subscription);
            $this->dispatchFailureNotifications($subscription, $e->getMessage(), $oldRetryCount);

            return new ChargeResult('failed', errorCode: 'chip_exception');
        }

        if (($charged['status'] ?? '') !== 'paid') {
            $oldRetryCount = $this->recordAttemptFailure($subscription);
            $this->dispatchFailureNotifications($subscription, null, $oldRetryCount);

            return new ChargeResult('failed', errorCode: 'payment_not_paid');
        }

        return $this->handleSuccess($subscription, $campaign, $donor, $charged, $grossAmount, $feeCoverAmount, CarbonImmutable::instance($now));
    }

    private function guardSubscriptionState(Subscription $subscription): ?ChargeResult
    {
        if (blank($subscription->chip_recurring_token)) {
            return new ChargeResult('failed', errorCode: 'not_chip_subscription');
        }

        if ($subscription->status !== SubscriptionStatus::Active) {
            return new ChargeResult('failed', errorCode: 'subscription_not_active');
        }

        if ($subscription->next_charge_at !== null && $subscription->next_charge_at->isFuture()) {
            return new ChargeResult('failed', errorCode: 'next_charge_in_future');
        }

        if ($subscription->paused_until !== null && $subscription->paused_until->isFuture()) {
            return new ChargeResult('failed', errorCode: 'subscription_paused');
        }

        return null;
    }

    private function returnUrl(Subscription $subscription): string
    {
        return route('home');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPseudoDonation(Subscription $subscription, int $totalCents): array
    {
        return [
            'donor' => $subscription->donor,
            'campaign' => $subscription->campaign,
            'gross_amount' => $totalCents / 100,
            'donor_fee_covered' => 0,
            'currency' => $subscription->currency,
            'public_id' => $subscription->public_id,
        ];
    }

    private function handleSuccess(
        Subscription $subscription,
        Campaign $campaign,
        Donor $donor,
        array $charged,
        float $grossAmount,
        float $feeCoverAmount,
        CarbonImmutable $now,
    ): ChargeResult {
        [$donation, $previousCollected] = DB::transaction(function () use (
            $subscription,
            $campaign,
            $donor,
            $charged,
            $grossAmount,
            $feeCoverAmount,
            $now,
        ): array {
            $campaign->refresh();
            $previousCollected = (float) $campaign->collected_amount;

            $donation = Donation::query()->create([
                'campaign_id' => $campaign->getKey(),
                'donor_id' => $donor->getKey(),
                'subscription_id' => $subscription->getKey(),
                'chip_purchase_id' => $charged['id'] ?? null,
                'gross_amount' => $grossAmount,
                'donor_fee_covered' => $feeCoverAmount,
                'currency' => $subscription->currency,
                'status' => DonationStatus::Succeeded,
                'type' => DonationType::Recurring,
                'source' => $subscription->source ?? 'checkout_modal',
                'stripe_fee' => ((float) ($charged['payment']['fee_amount'] ?? 0)) / 100,
                'net_amount' => ((float) ($charged['payment']['net_amount'] ?? 0)) / 100,
            ]);

            $campaign->increment('collected_amount', $grossAmount);

            $schedule = $this->scheduleRetry->afterSuccess($subscription, CarbonImmutable::instance($now));

            $subscription->update([
                'status' => $schedule['status'],
                'retry_count' => $schedule['retry_count'],
                'failed_installment_count' => $schedule['failed_installment_count'],
                'payment_count' => $subscription->payment_count + 1,
                'last_charge_at' => $now,
                'last_charge_attempt_at' => $now,
                'next_charge_at' => $schedule['next_charge_at'],
                'current_period_start' => $now,
                'current_period_end' => $schedule['next_charge_at'],
            ]);

            return [$donation, $previousCollected];
        });

        SendCampaignMilestoneNotification::dispatch($campaign, $previousCollected);
        SendNewDonationNotification::dispatch($donation);
        SendDonorRecurringPaymentNotification::dispatch($donation);
        SendLargeDonationNotification::dispatch($donation);
        SendMetaConversionEvent::dispatch($donation);
        SendLinkedInConversionEvent::dispatch($donation);
        SendXAdsConversionEvent::dispatch($donation);
        SendSnapchatConversionEvent::dispatch($donation);

        return new ChargeResult('succeeded', $donation);
    }

    private function recordAttemptFailure(Subscription $subscription): int
    {
        $oldRetryCount = $subscription->retry_count;
        $schedule = $this->scheduleRetry->afterFailure($subscription);

        $subscription->update([
            'status' => $schedule['status'],
            'retry_count' => $schedule['retry_count'],
            'failed_installment_count' => $schedule['failed_installment_count'],
            'last_charge_attempt_at' => now(),
            'next_charge_at' => $schedule['status'] === 'failed' ? null : $schedule['next_charge_at'],
        ]);

        return $oldRetryCount;
    }

    private function dispatchFailureNotifications(Subscription $subscription, ?string $errorMessage, int $oldRetryCount): void
    {
        SendFailedPaymentNotification::dispatch($subscription, $errorMessage);

        if ($subscription->donor?->canReceiveEmails()) {
            $isFinalAttempt = $subscription->status === SubscriptionStatus::Failed;
            SendDonorDunningNotification::dispatch($subscription, $oldRetryCount + 1, $isFinalAttempt);
        }
    }
}
