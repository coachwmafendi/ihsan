<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;

class PauseLocalRecurringPlan
{
    public function pause(Subscription $subscription, int $months = 1): void
    {
        $resumeAt = $subscription->next_charge_at
            ? $subscription->next_charge_at->addMonths($months)
            : now()->addMonths($months);

        $subscription->update([
            'status' => SubscriptionStatus::Paused,
            'paused_until' => $resumeAt,
            'next_charge_at' => $resumeAt,
        ]);
    }
}
