<?php

namespace App\Services;

use App\Enums\TrackingProvider;
use App\Models\Organization;
use App\Models\TrackingConfiguration;

class TrackingScriptService
{
    /**
     * Build frontend tracking configuration for an organization.
     *
     * @return array<string, mixed>
     */
    public static function config(Organization $organization): array
    {
        $configs = $organization->trackingConfigurations()
            ->where('is_enabled', true)
            ->get()
            ->keyBy(fn (TrackingConfiguration $config): string => $config->provider->value);

        return [
            'meta' => self::buildMetaConfig($configs->get(TrackingProvider::Meta->value)),
            'ga4' => self::buildGa4Config($configs->get(TrackingProvider::GoogleAnalytics4->value)),
            'google_ads' => self::buildGoogleAdsConfig($configs->get(TrackingProvider::GoogleAds->value)),
            'tiktok' => self::buildTikTokConfig($configs->get(TrackingProvider::TikTok->value)),
            'linkedin' => self::buildLinkedInConfig($configs->get(TrackingProvider::LinkedIn->value)),
            'x_ads' => self::buildXAdsConfig($configs->get(TrackingProvider::XAds->value)),
            'snapchat' => self::buildSnapchatConfig($configs->get(TrackingProvider::Snapchat->value)),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildMetaConfig(?TrackingConfiguration $config): ?array
    {
        if (! $config || ! $config->isConfigured()) {
            return null;
        }

        return [
            'enabled' => true,
            'pixel_id' => $config->credential('pixel_id'),
            'options' => [
                'track_page_views' => $config->option('track_page_views'),
                'track_donation_starts' => $config->option('track_donation_starts'),
                'track_successful_donations' => $config->option('track_successful_donations'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildGa4Config(?TrackingConfiguration $config): ?array
    {
        if (! $config || ! $config->isConfigured()) {
            return null;
        }

        return [
            'enabled' => true,
            'measurement_id' => $config->credential('measurement_id'),
            'options' => [
                'track_page_views' => $config->option('track_page_views'),
                'track_checkout_starts' => $config->option('track_checkout_starts'),
                'track_purchases' => $config->option('track_purchases'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildGoogleAdsConfig(?TrackingConfiguration $config): ?array
    {
        if (! $config || ! $config->isConfigured()) {
            return null;
        }

        return [
            'enabled' => true,
            'conversion_id' => $config->credential('conversion_id'),
            'conversion_label' => $config->credential('conversion_label'),
            'options' => [
                'track_conversions' => $config->option('track_conversions'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildTikTokConfig(?TrackingConfiguration $config): ?array
    {
        if (! $config || ! $config->isConfigured()) {
            return null;
        }

        return [
            'enabled' => true,
            'pixel_id' => $config->credential('pixel_id'),
            'options' => [
                'track_page_views' => $config->option('track_page_views'),
                'track_donation_events' => $config->option('track_donation_events'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildLinkedInConfig(?TrackingConfiguration $config): ?array
    {
        if (! $config || ! $config->isConfigured()) {
            return null;
        }

        return [
            'enabled' => true,
            'partner_id' => $config->credential('partner_id'),
            'conversion_id' => $config->credential('conversion_id'),
            'options' => [
                'track_page_views' => $config->option('track_page_views'),
                'track_donation_starts' => $config->option('track_donation_starts'),
                'track_conversions' => $config->option('track_conversions'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildXAdsConfig(?TrackingConfiguration $config): ?array
    {
        if (! $config || ! $config->isConfigured()) {
            return null;
        }

        return [
            'enabled' => true,
            'pixel_id' => $config->credential('pixel_id'),
            'conversion_id' => $config->credential('conversion_id'),
            'options' => [
                'track_page_views' => $config->option('track_page_views'),
                'track_donation_starts' => $config->option('track_donation_starts'),
                'track_conversions' => $config->option('track_conversions'),
            ],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function buildSnapchatConfig(?TrackingConfiguration $config): ?array
    {
        if (! $config || ! $config->isConfigured()) {
            return null;
        }

        return [
            'enabled' => true,
            'pixel_id' => $config->credential('pixel_id'),
            'options' => [
                'track_page_views' => $config->option('track_page_views'),
                'track_donation_starts' => $config->option('track_donation_starts'),
                'track_conversions' => $config->option('track_conversions'),
            ],
        ];
    }
}
