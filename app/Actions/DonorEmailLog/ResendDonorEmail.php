<?php

declare(strict_types=1);

namespace App\Actions\DonorEmailLog;

use App\Mail\CampaignCompletedDonorNotification;
use App\Mail\DonationReceipt;
use App\Mail\DonorDunningNotification;
use App\Mail\DonorNewSubscriptionNotification;
use App\Mail\DonorPaymentMethodExpiringNotification;
use App\Mail\DonorRecurringPaymentNotification;
use App\Mail\DonorRefundNotification;
use App\Mail\DonorSubscriptionCancelledNotification;
use App\Mail\SubscriptionAmountChangedNotification;
use App\Mail\SupporterSubscriptionAmountChangedNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\DonorEmailLog;
use App\Models\Subscription;
use App\Support\Currency;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ResendDonorEmail
{
    public function __construct(
        private LogDonorEmail $logger,
    ) {}

    public function handle(DonorEmailLog $log, ?string $toEmail = null): ?DonorEmailLog
    {
        $messageId = Str::uuid()->toString();
        $mailable = $this->recreateMailable($log, $messageId);

        if ($mailable === null) {
            return null;
        }

        $recipient = $toEmail ?? $log->donor->email;

        $metadata = $log->metadata ?? [];
        if ($toEmail !== null && $toEmail !== $log->donor->email) {
            $metadata['resent_to_email'] = $toEmail;
        }

        $newLog = $this->logger->handle(
            donor: $log->donor,
            mailable: $mailable,
            organization: $log->organization,
            donation: $log->donation,
            subscription: $log->subscription,
            resentFrom: $log,
            metadata: $metadata,
            messageId: $messageId,
        );

        Mail::to($recipient)->queue($mailable);

        return $newLog;
    }

    private function recreateMailable(DonorEmailLog $log, string $messageId): ?Mailable
    {
        return match ($log->mailable_class) {
            DonationReceipt::class => $this->recreateDonationReceipt($log, $messageId),
            CampaignCompletedDonorNotification::class => $this->recreateCampaignCompleted($log, $messageId),
            SubscriptionAmountChangedNotification::class => $this->recreateSubscriptionAmountChanged($log, $messageId),
            SupporterSubscriptionAmountChangedNotification::class => $this->recreateSupporterSubscriptionAmountChanged($log, $messageId),
            DonorDunningNotification::class => $this->recreateDonorDunning($log, $messageId),
            DonorNewSubscriptionNotification::class => $this->recreateDonorNewSubscription($log, $messageId),
            DonorRecurringPaymentNotification::class => $this->recreateDonorRecurringPayment($log, $messageId),
            DonorRefundNotification::class => $this->recreateDonorRefund($log, $messageId),
            DonorSubscriptionCancelledNotification::class => $this->recreateDonorSubscriptionCancelled($log, $messageId),
            DonorPaymentMethodExpiringNotification::class => $this->recreateDonorPaymentMethodExpiring($log, $messageId),
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

        return new SubscriptionAmountChangedNotification(
            subscription: $log->subscription,
            previousAmount: $previousAmount,
            isDonor: true,
            messageId: $messageId,
        );
    }

    private function recreateSupporterSubscriptionAmountChanged(DonorEmailLog $log, string $messageId): ?Mailable
    {
        if ($log->subscription === null) {
            return null;
        }

        return new SupporterSubscriptionAmountChangedNotification(
            $log->subscription,
            (float) ($log->metadata['previous_amount'] ?? $log->subscription->amount),
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

    private function recreateDonorNewSubscription(DonorEmailLog $log, string $messageId): ?Mailable
    {
        if ($log->donation === null) {
            return null;
        }

        return new DonorNewSubscriptionNotification($log->donation, $messageId);
    }

    private function recreateDonorRecurringPayment(DonorEmailLog $log, string $messageId): ?Mailable
    {
        if ($log->donation === null) {
            return null;
        }

        return new DonorRecurringPaymentNotification($log->donation, $messageId);
    }

    private function recreateDonorRefund(DonorEmailLog $log, string $messageId): ?Mailable
    {
        if ($log->donation === null) {
            return null;
        }

        return new DonorRefundNotification($log->donation, $this->formatAmount($log->donation), $messageId);
    }

    private function recreateDonorSubscriptionCancelled(DonorEmailLog $log, string $messageId): ?Mailable
    {
        if ($log->subscription === null) {
            return null;
        }

        return new DonorSubscriptionCancelledNotification($log->subscription, $messageId);
    }

    private function recreateDonorPaymentMethodExpiring(DonorEmailLog $log, string $messageId): ?Mailable
    {
        $metadata = $log->metadata ?? [];
        $daysRemaining = (int) ($metadata['days_remaining'] ?? 7);
        $subscriptionIds = (array) ($metadata['subscription_ids'] ?? []);

        if ($log->subscription !== null && empty($subscriptionIds)) {
            $subscriptionIds = [$log->subscription->getKey()];
        }

        $subscriptions = Subscription::query()->whereKey($subscriptionIds)->get();

        if ($subscriptions->isEmpty()) {
            return null;
        }

        return new DonorPaymentMethodExpiringNotification($subscriptions, $daysRemaining, $messageId);
    }

    private function formatAmount(Donation $donation): string
    {
        $symbol = Currency::symbol($donation->currency);
        $amount = number_format((float) $donation->gross_amount, 2);

        if (strtolower($donation->currency) !== 'myr' && $donation->base_amount !== null) {
            $base = number_format((float) $donation->base_amount, 2);

            return "≈ MYR {$base} ({$symbol} {$amount})";
        }

        return "{$symbol} {$amount}";
    }
}
