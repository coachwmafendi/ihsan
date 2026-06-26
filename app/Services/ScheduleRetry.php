<?php

namespace App\Services;

use App\Models\Subscription;
use Carbon\CarbonImmutable;

class ScheduleRetry
{
    /**
     * @param  array<int, int>|null  $retryIntervalsDays
     */
    public function __construct(
        private ?array $retryIntervalsDays = null,
        private ?int $maxFailedInstallments = null,
    ) {}

    /**
     * @return array{next_charge_at: CarbonImmutable, status: string, retry_count: int, failed_installment_count: int}
     */
    public function afterFailure(Subscription $subscription): array
    {
        $retryCount = $subscription->retry_count + 1;
        $intervals = $this->retryIntervalsDays ?? config('services.recurring.retry_intervals_days', [1, 3, 7, 7]);
        $index = min($retryCount - 1, count($intervals) - 1);
        $delayDays = $intervals[$index];

        $nextChargeAt = CarbonImmutable::now()->addDays($delayDays);

        $failedInstallmentCount = $subscription->failed_installment_count;
        $status = 'past_due';
        $maxRetries = count($intervals);

        if ($retryCount >= $maxRetries) {
            $failedInstallmentCount++;
            $retryCount = 0;

            $maxFailedInstallments = $this->maxFailedInstallments ?? (int) config('services.recurring.max_failed_installments', 6);
            if ($failedInstallmentCount >= $maxFailedInstallments) {
                $status = 'failed';
            }
        }

        return [
            'next_charge_at' => $status === 'failed' ? null : $nextChargeAt,
            'status' => $status,
            'retry_count' => $retryCount,
            'failed_installment_count' => $failedInstallmentCount,
        ];
    }

    public function afterSuccess(Subscription $subscription, CarbonImmutable $baseForNextCharge): array
    {
        return [
            'next_charge_at' => SubscriptionSchedule::nextChargeAt($baseForNextCharge, $subscription->interval),
            'status' => 'active',
            'retry_count' => 0,
            'failed_installment_count' => 0,
        ];
    }
}
