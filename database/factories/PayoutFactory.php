<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'stripe_payout_id' => 'po_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'amount' => fake()->numberBetween(1000, 100000),
            'currency' => 'myr',
            'status' => fake()->randomElement(['pending', 'in_transit', 'paid', 'failed']),
            'arrival_date' => fake()->dateTimeBetween('-1 year', '+1 week'),
        ];
    }
}
