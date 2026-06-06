<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Campaign>
 */
class CampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'public_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'target_amount' => 10000.00,
            'collected_amount' => 0,
            'has_target' => true,
            'allow_recurring' => true,
            'end_date' => now()->addMonth()->toDateString(),
            'status' => CampaignStatus::Active,
            'suggested_amounts' => [30, 50, 100],
        ];
    }
}
