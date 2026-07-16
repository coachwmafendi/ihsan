<?php

namespace Database\Factories;

use App\Mail\DonationReceipt;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorEmailLog>
 */
class DonorEmailLogFactory extends Factory
{
    protected $model = DonorEmailLog::class;

    public function definition(): array
    {
        return [
            'donor_id' => Donor::factory(),
            'organization_id' => Organization::factory(),
            'donation_id' => null,
            'subscription_id' => null,
            'resent_from_id' => null,
            'mailable_class' => DonationReceipt::class,
            'subject' => fake()->sentence(),
            'metadata' => null,
            'sent_at' => now(),
            'opened_at' => null,
            'public_id' => null,
        ];
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes) => [
            'delivery_status' => 'delivered',
            'delivered_at' => now(),
        ]);
    }

    public function donation(Donation $donation): static
    {
        return $this->state(fn (array $attributes) => [
            'donor_id' => $donation->donor_id,
            'organization_id' => $donation->campaign?->organization_id,
            'donation_id' => $donation->getKey(),
            'mailable_class' => DonationReceipt::class,
        ]);
    }

    public function subscription(Subscription $subscription): static
    {
        return $this->state(fn (array $attributes) => [
            'donor_id' => $subscription->donor_id,
            'organization_id' => $subscription->campaign?->organization_id,
            'subscription_id' => $subscription->getKey(),
        ]);
    }
}
