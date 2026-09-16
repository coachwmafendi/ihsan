<?php

namespace Database\Factories;

use App\Models\Donor;
use App\Models\DonorPaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DonorPaymentMethod>
 */
class DonorPaymentMethodFactory extends Factory
{
    protected $model = DonorPaymentMethod::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'donor_id' => Donor::factory(),
            'stripe_payment_method_id' => 'pm_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'brand' => 'Visa',
            'last4' => fake()->numerify('####'),
            'exp_month' => 12,
            'exp_year' => (int) now()->addYears(3)->format('Y'),
            'country' => 'MY',
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }
}
