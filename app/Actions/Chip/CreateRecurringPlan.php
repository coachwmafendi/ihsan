<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Subscription;

final class CreateRecurringPlan
{
    public function create(Donation $donation, string $recurringToken): Subscription
    {
        $subscription = Subscription::create([
            'campaign_id' => $donation->campaign_id,
            'donor_id' => $donation->donor_id,
            'source' => $donation->source,
            'amount' => $donation->gross_amount,
            'currency' => $donation->currency,
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
            'chip_recurring_token' => $recurringToken,
            'next_charge_at' => now()->addMonth(),
        ]);

        if ($donation->subscription_id === null) {
            $donation->update(['subscription_id' => $subscription->id]);
        }

        return $subscription;
    }
}
