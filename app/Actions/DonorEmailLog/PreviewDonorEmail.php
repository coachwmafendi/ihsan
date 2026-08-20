<?php

declare(strict_types=1);

namespace App\Actions\DonorEmailLog;

use App\Mail\CampaignCompletedDonorNotification;
use App\Mail\DonationReceipt;
use App\Mail\DonorDunningNotification;
use App\Mail\DonorNewSubscriptionNotification;
use App\Mail\DonorPaymentMethodChangedNotification;
use App\Mail\DonorPaymentMethodExpiringNotification;
use App\Mail\DonorRecurringPaymentNotification;
use App\Mail\DonorRefundNotification;
use App\Mail\DonorSubscriptionCancelledNotification;
use App\Mail\DonorSubscriptionFailedNotification;
use App\Mail\SubscriptionAmountChangedNotification;
use App\Mail\SupporterSubscriptionAmountChangedNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\DonorEmailLog;
use App\Models\DonorPaymentMethod;
use App\Models\Subscription;
use App\Support\Currency;
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
            SupporterSubscriptionAmountChangedNotification::class => $this->recreateSupporterSubscriptionAmountChanged($log),
            DonorDunningNotification::class => $this->recreateDonorDunning($log),
            DonorNewSubscriptionNotification::class => $this->recreateDonorNewSubscription($log),
            DonorRecurringPaymentNotification::class => $this->recreateDonorRecurringPayment($log),
            DonorRefundNotification::class => $this->recreateDonorRefund($log),
            DonorSubscriptionCancelledNotification::class => $this->recreateDonorSubscriptionCancelled($log),
            DonorPaymentMethodExpiringNotification::class => $this->recreateDonorPaymentMethodExpiring($log),
            DonorPaymentMethodChangedNotification::class => $this->recreateDonorPaymentMethodChanged($log),
            DonorSubscriptionFailedNotification::class => $this->recreateDonorSubscriptionFailed($log),
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

        return new SubscriptionAmountChangedNotification(
            subscription: $log->subscription,
            previousAmount: $previousAmount,
            isDonor: true,
        );
    }

    private function recreateSupporterSubscriptionAmountChanged(DonorEmailLog $log): ?Mailable
    {
        if ($log->subscription === null) {
            return null;
        }

        return new SupporterSubscriptionAmountChangedNotification(
            $log->subscription,
            (float) ($log->metadata['previous_amount'] ?? $log->subscription->amount),
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

    private function recreateDonorRefund(DonorEmailLog $log): ?Mailable
    {
        if ($log->donation === null) {
            return null;
        }

        return new DonorRefundNotification($log->donation, $this->formatAmount($log->donation));
    }

    private function recreateDonorSubscriptionCancelled(DonorEmailLog $log): ?Mailable
    {
        if ($log->subscription === null) {
            return null;
        }

        return new DonorSubscriptionCancelledNotification($log->subscription);
    }

    private function recreateDonorPaymentMethodExpiring(DonorEmailLog $log): ?Mailable
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

        return new DonorPaymentMethodExpiringNotification($subscriptions, $daysRemaining);
    }

    private function recreateDonorPaymentMethodChanged(DonorEmailLog $log): ?Mailable
    {
        if ($log->subscription === null) {
            return null;
        }

        $metadata = $log->metadata ?? [];
        $newPaymentMethodId = $metadata['new_payment_method_id'] ?? null;
        $previousPaymentMethodId = $metadata['previous_payment_method_id'] ?? null;

        $newPaymentMethod = $newPaymentMethodId
            ? DonorPaymentMethod::query()->find($newPaymentMethodId)
            : $log->subscription->donorPaymentMethod;

        $previousPaymentMethod = $previousPaymentMethodId
            ? DonorPaymentMethod::query()->find($previousPaymentMethodId)
            : null;

        if ($newPaymentMethod === null) {
            return null;
        }

        return new DonorPaymentMethodChangedNotification(
            $log->subscription,
            $previousPaymentMethod,
            $newPaymentMethod,
        );
    }

    private function recreateDonorSubscriptionFailed(DonorEmailLog $log): ?Mailable
    {
        if ($log->subscription === null) {
            return null;
        }

        return new DonorSubscriptionFailedNotification($log->subscription);
    }

    private function formatAmount(Donation $donation): string
    {
        $amount = Currency::format((string) $donation->currency, (float) $donation->gross_amount);

        if (strtolower((string) $donation->currency) !== 'myr' && $donation->base_amount !== null) {
            $base = Currency::format('MYR', (float) $donation->base_amount);

            return "{$base} ({$amount})";
        }

        return $amount;
    }
}
