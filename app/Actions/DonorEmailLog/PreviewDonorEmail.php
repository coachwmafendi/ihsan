<?php

declare(strict_types=1);

namespace App\Actions\DonorEmailLog;

use App\Mail\CampaignCompletedDonorNotification;
use App\Mail\DonationReceipt;
use App\Mail\DonorDunningNotification;
use App\Mail\DonorNewSubscriptionNotification;
use App\Mail\DonorRecurringPaymentNotification;
use App\Mail\SubscriptionAmountChangedNotification;
use App\Models\Campaign;
use App\Models\DonorEmailLog;
use Illuminate\Mail\Mailable;

class PreviewDonorEmail
{
    public function handle(DonorEmailLog $log): ?string
    {
        $mailable = $this->recreateMailable($log);

        if ($mailable === null) {
            return null;
        }

        try {
            return $mailable->render();
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }

    private function recreateMailable(DonorEmailLog $log): ?Mailable
    {
        return match ($log->mailable_class) {
            DonationReceipt::class => $this->recreateDonationReceipt($log),
            CampaignCompletedDonorNotification::class => $this->recreateCampaignCompleted($log),
            SubscriptionAmountChangedNotification::class => $this->recreateSubscriptionAmountChanged($log),
            DonorDunningNotification::class => $this->recreateDonorDunning($log),
            DonorNewSubscriptionNotification::class => $this->recreateDonorNewSubscription($log),
            DonorRecurringPaymentNotification::class => $this->recreateDonorRecurringPayment($log),
            default => null,
        };
    }

    private function recreateDonationReceipt(DonorEmailLog $log): ?Mailable
    {
        if ($log->donation === null) {
            return null;
        }

        return new DonationReceipt($log->donation);
    }

    private function recreateCampaignCompleted(DonorEmailLog $log): ?Mailable
    {
        $campaign = $log->metadata['campaign_id']
            ? Campaign::query()->find($log->metadata['campaign_id'])
            : ($log->donation?->campaign ?? $log->subscription?->campaign);

        if ($campaign === null) {
            return null;
        }

        return new CampaignCompletedDonorNotification($campaign, $log->donor);
    }

    private function recreateSubscriptionAmountChanged(DonorEmailLog $log): ?Mailable
    {
        if ($log->subscription === null) {
            return null;
        }

        $metadata = $log->metadata ?? [];
        $previousAmount = (float) ($metadata['previous_amount'] ?? $log->subscription->amount);
        $amountDisplay = $metadata['amount_display'] ?? $log->subscription->currency_symbol.' '.number_format($log->subscription->amount, 2);

        return new SubscriptionAmountChangedNotification(
            $log->subscription,
            $previousAmount,
            $amountDisplay,
            true,
        );
    }

    private function recreateDonorDunning(DonorEmailLog $log): ?Mailable
    {
        if ($log->subscription === null) {
            return null;
        }

        $metadata = $log->metadata ?? [];
        $retryCount = (int) ($metadata['retry_count'] ?? 1);
        $isFinalAttempt = (bool) ($metadata['is_final_attempt'] ?? false);

        return new DonorDunningNotification($log->subscription, $retryCount, $isFinalAttempt);
    }

    private function recreateDonorNewSubscription(DonorEmailLog $log): ?Mailable
    {
        if ($log->donation === null) {
            return null;
        }

        return new DonorNewSubscriptionNotification($log->donation);
    }

    private function recreateDonorRecurringPayment(DonorEmailLog $log): ?Mailable
    {
        if ($log->donation === null) {
            return null;
        }

        return new DonorRecurringPaymentNotification($log->donation);
    }
}
