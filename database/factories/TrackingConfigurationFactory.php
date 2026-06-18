<?php

namespace Database\Factories;

use App\Enums\TrackingProvider;
use App\Enums\TrackingProviderStatus;
use App\Models\Organization;
use App\Models\TrackingConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackingConfiguration>
 */
class TrackingConfigurationFactory extends Factory
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
            'provider' => TrackingProvider::Meta,
            'is_enabled' => true,
            'status' => TrackingProviderStatus::Active,
            'credentials' => [
                'pixel_id' => fake()->numerify('###############'),
                'access_token' => fake()->sha256(),
            ],
            'options' => [
                'track_page_views' => true,
                'track_donation_starts' => true,
                'track_successful_donations' => true,
            ],
        ];
    }

    public function forProvider(TrackingProvider $provider): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => $provider,
            'credentials' => $provider->defaultCredentials(),
            'options' => $provider->defaultOptions(),
        ]);
    }

    public function meta(array $credentials = [], array $options = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => TrackingProvider::Meta,
            'credentials' => array_merge([
                'pixel_id' => fake()->numerify('###############'),
                'access_token' => fake()->sha256(),
            ], $credentials),
            'options' => array_merge([
                'track_page_views' => true,
                'track_donation_starts' => true,
                'track_successful_donations' => true,
            ], $options),
        ]);
    }

    public function ga4(array $credentials = [], array $options = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => TrackingProvider::GoogleAnalytics4,
            'credentials' => array_merge([
                'measurement_id' => 'G-'.fake()->regexify('[A-Z0-9]{10}'),
            ], $credentials),
            'options' => array_merge([
                'track_page_views' => true,
                'track_checkout_starts' => true,
                'track_purchases' => true,
            ], $options),
        ]);
    }

    public function googleAds(array $credentials = [], array $options = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => TrackingProvider::GoogleAds,
            'credentials' => array_merge([
                'conversion_id' => 'AW-'.fake()->numerify('#########'),
                'conversion_label' => fake()->regexify('[a-zA-Z0-9_]{20}'),
            ], $credentials),
            'options' => array_merge([
                'track_conversions' => true,
            ], $options),
        ]);
    }

    public function tiktok(array $credentials = [], array $options = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => TrackingProvider::TikTok,
            'credentials' => array_merge([
                'pixel_id' => 'C'.fake()->regexify('[A-Z0-9]{17}'),
                'access_token' => fake()->sha256(),
            ], $credentials),
            'options' => array_merge([
                'track_page_views' => true,
                'track_donation_events' => true,
            ], $options),
        ]);
    }

    public function linkedin(array $credentials = [], array $options = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => TrackingProvider::LinkedIn,
            'credentials' => array_merge([
                'partner_id' => (string) fake()->randomNumber(7, true),
                'conversion_id' => (string) fake()->randomNumber(8, true),
                'access_token' => fake()->sha256(),
            ], $credentials),
            'options' => array_merge([
                'track_page_views' => true,
                'track_donation_starts' => true,
                'track_conversions' => true,
            ], $options),
        ]);
    }

    public function xAds(array $credentials = [], array $options = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => TrackingProvider::XAds,
            'credentials' => array_merge([
                'pixel_id' => 'o'.fake()->randomNumber(4, true),
                'conversion_id' => 'tw-o'.fake()->randomNumber(4, true).'-'.fake()->regexify('[a-z]{5}'),
                'access_token' => fake()->sha256(),
            ], $credentials),
            'options' => array_merge([
                'track_page_views' => true,
                'track_donation_starts' => true,
                'track_conversions' => true,
            ], $options),
        ]);
    }

    public function snapchat(array $credentials = [], array $options = []): static
    {
        return $this->state(fn (array $attributes): array => [
            'provider' => TrackingProvider::Snapchat,
            'credentials' => array_merge([
                'pixel_id' => fake()->uuid(),
                'access_token' => fake()->sha256(),
            ], $credentials),
            'options' => array_merge([
                'track_page_views' => true,
                'track_donation_starts' => true,
                'track_conversions' => true,
            ], $options),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_enabled' => false,
            'status' => TrackingProviderStatus::NotConfigured,
        ]);
    }
}
