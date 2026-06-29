<?php

declare(strict_types=1);

namespace App\Actions\Chip;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Subscription;
use InvalidArgumentException;

final class CreateRecurringPlan
{
    public function create(Donation $donation, string $recurringToken): Subscription
    {
        if ($donation->type !== DonationType::Recurring) {
            throw new InvalidArgumentException('Donation must be recurring to create a CHIP recurring plan.');
        }

        if ($donation->status !== DonationStatus::Succeeded) {
            throw new InvalidArgumentException('Donation must be succeeded to create a CHIP recurring plan.');
        }

        $subscription = Subscription::create([
            'campaign_id' => $donation->campaign_id,
            'donor_id' => $donation->donor_id,
            'source' => $donation->source,
            'amount' => $donation->gross_amount,
            'currency' => $donation->currency,
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
            'chip_recurring_token' => $recurringToken,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
            'next_charge_at' => now()->addMonth(),
        ]);

        if ($donation->subscription_id === null) {
            $donation->update(['subscription_id' => $subscription->id]);
        }

        return $subscription;
    }
}
