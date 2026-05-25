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
            'token' => Str::random(6),
            'type' => ElementType::Form,
            'config' => [
                'title' => 'Your most generous donation',
                'text_color' => '#212830',
                'background_color' => '#FFFFFF',
                'icon_color' => '#FF435A',
                'border_size' => 2,
                'border_radius' => 6,
                'border_color' => '#DEDFF3',
                'show_shadow' => false,
                'submit_text' => 'Donate and Support',
                'suggested_amounts' => [200, 100, 50, 30, 10, 5],
                'default_amount' => 5,
                'default_frequency' => 'monthly',
                'allow_monthly' => true,
                'show_dedication' => true,
                'show_comment' => true,
                'button_text' => 'Donate Now',
                'button_color' => 'bg-teal-600 hover:bg-teal-700',
                'button_size' => 'text-base px-6 py-3',
                'corner_radius' => 8,
                'show_name' => true,
                'show_email' => true,
                'show_phone' => false,
                'show_message' => true,
                'show_suggested' => true,
                'show_amount_input' => true,
                'success_action' => 'message',
                'success_message' => 'Thank you for your donation!',
                'thank_you_title' => 'Thank You!',
                'thank_you_description' => '',
            ],
            'is_active' => true,
        ];
    }
}
