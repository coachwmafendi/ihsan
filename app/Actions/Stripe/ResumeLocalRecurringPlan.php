<?php

namespace App\Actions\Stripe;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\SubscriptionSchedule;
use Carbon\CarbonImmutable;

class ResumeLocalRecurringPlan
{
    public function resume(Subscription $subscription): void
    {
        $base = CarbonImmutable::instance($subscription->next_charge_at ?? now());
        $nextChargeAt = SubscriptionSchedule::nextChargeAt($base, $subscription->interval);

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'paused_until' => null,
            'next_charge_at' => $nextChargeAt,
        ]);
    }
}
