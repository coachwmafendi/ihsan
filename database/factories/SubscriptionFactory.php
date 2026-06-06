<?php

namespace Database\Factories;

use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'donor_id' => Donor::factory(),
            'public_id' => null,
            'stripe_subscription_id' => 'sub_'.fake()->unique()->bothify('????????????'),
            'stripe_price_id' => 'price_'.fake()->unique()->bothify('????????????'),
            'amount' => 30.00,
            'currency' => 'myr',
            'interval' => SubscriptionInterval::Monthly,
            'status' => SubscriptionStatus::Active,
            'retry_count' => 0,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ];
    }
}
