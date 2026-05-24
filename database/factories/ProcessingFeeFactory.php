<?php

namespace Database\Factories;

use App\Models\Donation;
use App\Models\MonthlyInvoice;
use App\Models\Organization;
use App\Models\ProcessingFee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProcessingFee>
 */
class ProcessingFeeFactory extends Factory
{
    protected $model = ProcessingFee::class;

    public function definition(): array
    {
        return [
            'donation_id' => Donation::factory(),
            'organization_id' => Organization::factory(),
            'fee_amount' => fake()->randomFloat(2, 0.5, 100),
            'fee_percentage' => 2.5,
            'status' => 'pending',
            'stripe_transfer_id' => null,
        ];
    }

    public function invoiced(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'invoiced',
            'monthly_invoice_id' => MonthlyInvoice::factory(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'monthly_invoice_id' => MonthlyInvoice::factory(),
        ]);
    }
}
