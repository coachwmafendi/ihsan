<?php

namespace Database\Factories;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donation>
 */
class DonationFactory extends Factory
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
            'subscription_id' => null,
            'gross_amount' => 100.00,
            'base_amount' => 100.00,
            'stripe_fee' => 3.50,
            'processing_fee' => 3.00,
            'net_amount' => 93.50,
            'currency' => 'myr',
            'status' => DonationStatus::Succeeded,
            'type' => DonationType::OneTime,
            'is_anonymous' => false,
            'utm_params' => [
                'source' => 'direct',
            ],
        ];
    }
}
