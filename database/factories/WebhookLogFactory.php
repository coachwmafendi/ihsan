<?php

namespace Database\Factories;

use App\Models\WebhookLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookLog>
 */
class WebhookLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stripe_event_id' => 'evt_'.fake()->unique()->bothify('????????????'),
            'event_type' => 'checkout.session.completed',
            'payload' => [
                'id' => 'evt_test',
                'type' => 'checkout.session.completed',
            ],
            'status' => 'received',
        ];
    }
}
