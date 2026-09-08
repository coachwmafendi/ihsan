<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\SubscriptionStatus;
use App\Jobs\SendDonorSubscriptionCancelledNotification;
use App\Models\Subscription;
use App\Services\ChipApi;
use Throwable;

/**
 * The audit entry belongs to the caller, which is the only place that knows
 * whether the supporter or an admin asked. See CancelLocalRecurringPlan.
 */
class CancelRecurringToken
{
    public function __construct(private ChipApi $chipApi) {}

    public function cancel(Subscription $subscription): void
    {
        $subscription->loadMissing('campaign.organization');

        $organization = $subscription->campaign?->organization;

        if ($organization !== null && filled($subscription->chip_recurring_token)) {
            try {
                $this->chipApi->deleteRecurringToken((string) $subscription->chip_recurring_token, $organization);
            } catch (Throwable $e) {
                report($e);
            }
        }

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_at_period_end' => false,
            'next_charge_at' => null,
        ]);

        SendDonorSubscriptionCancelledNotification::dispatch($subscription);
    }
}
