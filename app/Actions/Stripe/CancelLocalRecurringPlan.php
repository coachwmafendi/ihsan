<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionStatus;
use App\Jobs\SendDonorSubscriptionCancelledNotification;
use App\Models\Subscription;
use App\Services\SubscriptionActivityLogger;

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

        SubscriptionActivityLogger::cancelled(
            $subscription,
            $immediately ? 'Cancelled immediately' : 'Cancelled at period end',
            auth()->user(),
            ['source' => 'manual']
        );

        if ($immediately) {
            SendDonorSubscriptionCancelledNotification::dispatch($subscription);
        }
    }
}
