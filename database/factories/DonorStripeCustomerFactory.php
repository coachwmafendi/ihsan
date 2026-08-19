<?php

namespace Database\Factories;

use App\Models\Donor;
use App\Models\DonorStripeCustomer;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorStripeCustomer>
 */
class DonorStripeCustomerFactory extends Factory
{
    protected $model = DonorStripeCustomer::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donor_id' => Donor::factory(),
            'organization_id' => Organization::factory(),
            'stripe_customer_id' => 'cus_'.fake()->regexify('[A-Za-z0-9]{14}'),
        ];
    }
}
