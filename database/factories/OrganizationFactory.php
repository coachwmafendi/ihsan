<?php

namespace Database\Factories;

use App\Enums\OrganizationStatus;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Organization>
 */
class OrganizationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function stripeConnected(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_account_id' => 'acct_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_onboarded' => true,
        ]);
    }

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'code' => fake()->unique()->regexify('[A-Z]{8}'),
            'registration_type' => 'others',
            'description' => fake()->paragraph(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'status' => OrganizationStatus::Active,
            'stripe_account_id' => 'acct_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_onboarded' => true,
            'settings' => [
                'primary_color' => '#0f766e',
                'suggested_amounts' => [30, 50, 100],
            ],
        ];
    }

    public function withoutStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_account_id' => null,
            'stripe_onboarded' => false,
        ]);
    }

    public function stripePending(): static
    {
        return $this->state(fn (array $attributes) => [
            'stripe_account_id' => 'acct_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_onboarded' => false,
        ]);
    }
}
