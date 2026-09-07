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
        // Step forward from where the plan started, not from the paused date.
        // Adding an interval to a date already months away pushed the next
        // charge further out every time anyone resumed.
        $anchor = CarbonImmutable::instance($subscription->created_at ?? now());

        $nextChargeAt = SubscriptionSchedule::nextChargeAfter(
            $anchor,
            $subscription->interval,
            CarbonImmutable::now(),
        );

        $subscription->update([
            'status' => SubscriptionStatus::Active,
            'paused_until' => null,
            'next_charge_at' => $nextChargeAt,
        ]);
    }
}
