<?php

namespace Database\Factories\Fraud;

use App\Models\Donation;
use App\Models\Fraud\BlockedDonation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BlockedDonation>
 */
class BlockedDonationFactory extends Factory
{
    protected $model = BlockedDonation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donation_id' => Donation::factory(),
            'reason' => $this->faker->sentence(),
            'review_status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'review_notes' => null,
        ];
    }
}
