<?php

namespace Database\Factories;

use App\Models\MonthlyInvoice;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyInvoice>
 */
class MonthlyInvoiceFactory extends Factory
{
    protected $model = MonthlyInvoice::class;

    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'public_id' => null,
            'stripe_invoice_id' => 'in_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'invoice_number' => 'INV-'.now()->format('Ymd').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'period' => now()->startOfMonth()->subMonth(),
            'total_fees' => fake()->randomFloat(2, 10, 1000),
            'stripe_status' => 'open',
            'stripe_invoice_url' => fake()->url(),
            'stripe_invoice_pdf' => fake()->url(),
        ];
    }
}
