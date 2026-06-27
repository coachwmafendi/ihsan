<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionStatus;
use App\Jobs\SendDonorSubscriptionCancelledNotification;
use App\Models\Subscription;

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
