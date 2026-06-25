<?php

namespace Database\Factories;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Donor>
 */
class DonorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'public_id' => null,
            'first_name' => null,
            'last_name' => null,
            'name' => fake()->name(),
            'title' => fake()->optional()->randomElement(['Mr', 'Ms', 'Mrs', 'Dr']),
            'occupation' => fake()->optional()->jobTitle(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->optional()->phoneNumber(),
            'address_city' => fake()->optional()->city(),
            'country' => fake()->optional()->randomElement(['my', 'sg', 'id']),
            'photo_path' => null,
            'email_opt_out_at' => null,
        ];
    }
}
