<?php

namespace Database\Factories;

use App\Models\Donation;
use App\Models\Organization;
use App\Models\PlatformFee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformFee>
 */
class PlatformFeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donation_id' => Donation::factory(),
            'organization_id' => Organization::factory(),
            'fee_amount' => 3.00,
            'fee_percentage' => 3.00,
            'status' => 'pending',
        ];
    }
}
