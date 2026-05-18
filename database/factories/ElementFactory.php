<?php

namespace Database\Factories;

use App\Enums\ElementType;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Element>
 */
class ElementFactory extends Factory
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
            'campaign_id' => null,
            'name' => fake()->words(2, true),
            'token' => Str::random(32),
            'type' => ElementType::Form,
            'config' => [
                'theme' => 'light',
                'primary_color' => '#0f766e',
                'suggested_amounts' => [30, 50, 100],
                'default_frequency' => 'monthly',
                'button_label' => 'Donate',
            ],
            'is_active' => true,
        ];
    }
}
