<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionStatus;
use App\Jobs\SendDonorSubscriptionCancelledNotification;
use App\Models\Subscription;

/**
 * Writing the audit entry is the caller's job, not this action's. Only the
 * caller knows whether the supporter or an admin asked for it, and while this
 * class logged as well every cancellation from the donor portal was recorded
 * twice.
 */
class CancelLocalRecurringPlan
{
    public function cancel(Subscription $subscription, bool $immediately = true): void
    {
        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
            'cancel_at_period_end' => ! $immediately,
            'next_charge_at' => null,
        ]);

        if ($immediately) {
            SendDonorSubscriptionCancelledNotification::dispatch($subscription);
        }
    }
}
