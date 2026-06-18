<?php

declare(strict_types=1);

namespace App\Actions\DonorEmailLog;

use App\Mail\CampaignCompletedDonorNotification;
use App\Mail\DonationReceipt;
use App\Mail\DonorDunningNotification;
use App\Mail\SubscriptionAmountChangedNotification;
use App\Models\Campaign;
use App\Models\DonorEmailLog;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResendDonorEmail
{
    public function __construct(
        private LogDonorEmail $logger,
    ) {}

    public function handle(DonorEmailLog $log): ?DonorEmailLog
    {
        $messageId = Str::uuid()->toString();
        $mailable = $this->recreateMailable($log, $messageId);

        if ($mailable === null) {
            return null;
        }

        $newLog = $this->logger->handle(
            donor: $log->donor,
            mailable: $mailable,
            organization: $log->organization,
            donation: $log->donation,
            subscription: $log->subscription,
            resentFrom: $log,
            metadata: $log->metadata ?? [],
            messageId: $messageId,
        );

        Mail::to($log->donor->email)->queue($mailable);

        return $newLog;
    }

    private function recreateMailable(DonorEmailLog $log, string $messageId): ?Mailable
    {
        return match ($log->mailable_class) {
            DonationReceipt::class => $this->recreateDonationReceipt($log, $messageId),
            CampaignCompletedDonorNotification::class => $this->recreateCampaignCompleted($log, $messageId),
            SubscriptionAmountChangedNotification::class => $this->recreateSubscriptionAmountChanged($log, $messageId),
            DonorDunningNotification::class => $this->recreateDonorDunning($log, $messageId),
            default => null,
        };
    }

    private function recreateDonationReceipt(DonorEmailLog $log, string $messageId): ?Mailable
    {
        if ($log->donation === null) {
            return null;
        }

        return new DonationReceipt($log->donation, $messageId);
    }

    private function recreateCampaignCompleted(DonorEmailLog $log, string $messageId): ?Mailable
    {
        $campaign = $log->metadata['campaign_id']
            ? Campaign::query()->find($log->metadata['campaign_id'])
            : ($log->donation?->campaign ?? $log->subscription?->campaign);

        if ($campaign === null) {
            return null;
        }

        return new CampaignCompletedDonorNotification($campaign, $log->donor, $messageId);
    }

    private function recreateSubscriptionAmountChanged(DonorEmailLog $log, string $messageId): ?Mailable
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
            $messageId,
        );
    }

    private function recreateDonorDunning(DonorEmailLog $log, string $messageId): ?Mailable
    {
        if ($log->subscription === null) {
            return null;
        }

        $metadata = $log->metadata ?? [];
        $retryCount = (int) ($metadata['retry_count'] ?? 1);
        $isFinalAttempt = (bool) ($metadata['is_final_attempt'] ?? false);

        return new DonorDunningNotification($log->subscription, $retryCount, $isFinalAttempt, $messageId);
    }
}
