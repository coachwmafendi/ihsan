<?php

namespace App\Enums;

use App\Models\TrackingConfiguration;

enum TrackingProvider: string
{
    case Meta = 'meta';
    case GoogleAnalytics4 = 'ga4';
    case GoogleAds = 'google_ads';
    case TikTok = 'tiktok';
    case LinkedIn = 'linkedin';
    case XAds = 'x_ads';
    case Snapchat = 'snapchat';

    public function label(): string
    {
        return match ($this) {
            self::Meta => 'Meta Ads',
            self::GoogleAnalytics4 => 'Google Analytics 4',
            self::GoogleAds => 'Google Ads',
            self::TikTok => 'TikTok Ads',
            self::LinkedIn => 'LinkedIn Ads',
            self::XAds => 'X Ads',
            self::Snapchat => 'Snapchat Ads',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Meta => 'Track donations from Facebook and Instagram campaigns.',
            self::GoogleAnalytics4 => 'Measure donor journeys and conversion funnels.',
            self::GoogleAds => 'Send completed donation events to Google Ads.',
            self::TikTok => 'Track conversions from TikTok campaigns.',
            self::LinkedIn => 'Measure donations attributed to LinkedIn campaigns.',
            self::XAds => 'Track conversions from X (Twitter) ad campaigns.',
            self::Snapchat => 'Track donations attributed to Snapchat campaigns.',
        };
    }

    /**
     * Credential fields — rendered as form inputs in the settings UI.
     * type: text | password
     * All values stored encrypted in the database.
     *
     * @return array<int, array{key: string, label: string, type: string, placeholder: string, hint?: string}>
     */
    public function credentialFields(): array
    {
        return match ($this) {
            self::Meta => [
                ['key' => 'pixel_id', 'label' => 'Meta Pixel ID', 'type' => 'text', 'placeholder' => '123456789012345'],
                ['key' => 'access_token', 'label' => 'Conversion API Access Token', 'type' => 'text', 'placeholder' => 'EAAGsbA1E4d6BA...'],
            ],
            self::GoogleAnalytics4 => [
                ['key' => 'measurement_id', 'label' => 'Measurement ID', 'type' => 'text', 'placeholder' => 'G-XXXXXXXXXX', 'hint' => 'Found in GA4 → Admin → Data Streams → your stream.'],
                ['key' => 'api_secret', 'label' => 'Measurement Protocol API Secret', 'type' => 'text', 'placeholder' => '••••••••••••••••', 'hint' => 'Found in GA4 → Admin → Data Streams → your stream → Measurement Protocol API secrets.'],
            ],
            self::GoogleAds => [
                ['key' => 'conversion_id', 'label' => 'Conversion ID', 'type' => 'text', 'placeholder' => 'AW-XXXXXXXXXX'],
                ['key' => 'conversion_label', 'label' => 'Conversion Label', 'type' => 'text', 'placeholder' => 'XXXXXXXXXXXXXXXXXXX', 'hint' => 'Found in Google Ads → Goals → Conversions.'],
            ],
            self::TikTok => [
                ['key' => 'pixel_id', 'label' => 'Pixel ID', 'type' => 'text', 'placeholder' => 'CXXXXXXXXXXXXXXXX'],
                ['key' => 'access_token', 'label' => 'Access Token', 'type' => 'text', 'placeholder' => '••••••••••••••••'],
            ],
            self::LinkedIn => [
                ['key' => 'partner_id', 'label' => 'Partner ID', 'type' => 'text', 'placeholder' => '1234567'],
                ['key' => 'conversion_id', 'label' => 'Conversion ID', 'type' => 'text', 'placeholder' => '12345678'],
                ['key' => 'access_token', 'label' => 'Conversion API Access Token', 'type' => 'text', 'placeholder' => '••••••••••••••••'],
            ],
            self::XAds => [
                ['key' => 'pixel_id', 'label' => 'Pixel ID', 'type' => 'text', 'placeholder' => 'o1234'],
                ['key' => 'conversion_id', 'label' => 'Conversion Event ID', 'type' => 'text', 'placeholder' => 'tw-o1234-abcde'],
                ['key' => 'access_token', 'label' => 'Conversion API Access Token', 'type' => 'text', 'placeholder' => '••••••••••••••••'],
            ],
            self::Snapchat => [
                ['key' => 'pixel_id', 'label' => 'Pixel ID', 'type' => 'text', 'placeholder' => 'xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx'],
                ['key' => 'access_token', 'label' => 'Conversion API Token', 'type' => 'text', 'placeholder' => '••••••••••••••••'],
            ],
        };
    }

    /**
     * Toggle options — what events this provider can track.
     *
     * @return array<int, array{key: string, label: string, description: string}>
     */
    public function optionFields(): array
    {
        return match ($this) {
            self::Meta => [
                ['key' => 'track_page_views', 'label' => 'Track Page Views', 'description' => 'Send PageView events when donors land on your pages.'],
                ['key' => 'track_donation_starts', 'label' => 'Track Donation Starts', 'description' => 'Fire InitiateCheckout when donors begin the form.'],
                ['key' => 'track_successful_donations', 'label' => 'Track Successful Donations', 'description' => 'Send Purchase events after each successful donation.'],
            ],
            self::GoogleAnalytics4 => [
                ['key' => 'track_page_views', 'label' => 'Track Page Views', 'description' => 'Send page_view events automatically.'],
                ['key' => 'track_checkout_starts', 'label' => 'Track Checkout Starts', 'description' => 'Send begin_checkout when donors enter the form.'],
                ['key' => 'track_purchases', 'label' => 'Track Purchases', 'description' => 'Send purchase events with donation value.'],
            ],
            self::GoogleAds => [
                ['key' => 'track_conversions', 'label' => 'Track Donation Conversions', 'description' => 'Report donation value and currency to Google Ads.'],
            ],
            self::TikTok => [
                ['key' => 'track_page_views', 'label' => 'Track Page Views', 'description' => 'Send ViewContent events on each page load.'],
                ['key' => 'track_donation_events', 'label' => 'Track Donation Events', 'description' => 'Send CompletePayment events with donation value.'],
            ],
            self::LinkedIn => [
                ['key' => 'track_page_views', 'label' => 'Emit noscript page-view fallback', 'description' => 'Emit the LinkedIn noscript PageView pixel when the Insight Tag loads.'],
                ['key' => 'track_donation_starts', 'label' => 'Track Donation Starts', 'description' => 'Fire a conversion event when donors begin checkout.'],
                ['key' => 'track_conversions', 'label' => 'Track Donation Conversions', 'description' => 'Report completed donations as LinkedIn conversions.'],
            ],
            self::XAds => [
                ['key' => 'track_page_views', 'label' => 'Track Page Views', 'description' => 'Emit a PageView event when the X Pixel loads.'],
                ['key' => 'track_donation_starts', 'label' => 'Track Donation Starts', 'description' => 'Fire a checkout-start conversion event.'],
                ['key' => 'track_conversions', 'label' => 'Track Donation Conversions', 'description' => 'Report completed donations as X conversion events.'],
            ],
            self::Snapchat => [
                ['key' => 'track_page_views', 'label' => 'Track Page Views', 'description' => 'Send PAGE_VIEW events when the Snap Pixel loads.'],
                ['key' => 'track_donation_starts', 'label' => 'Track Donation Starts', 'description' => 'Send START_CHECKOUT events when donors begin checkout.'],
                ['key' => 'track_conversions', 'label' => 'Track Donation Conversions', 'description' => 'Send PURCHASE events with donation value.'],
            ],
        };
    }

    /** @return array<string, mixed> */
    public function defaultCredentials(): array
    {
        return array_fill_keys(
            array_column($this->credentialFields(), 'key'),
            ''
        );
    }

    /** @return array<string, bool> */
    public function defaultOptions(): array
    {
        return array_fill_keys(
            array_column($this->optionFields(), 'key'),
            true
        );
    }

    public function primaryCredentialKey(): string
    {
        return $this->credentialFields()[0]['key'];
    }

    public function isConfigured(TrackingConfiguration $config): bool
    {
        $credentials = $config->credentials ?? [];

        return ! empty($credentials[$this->primaryCredentialKey()]);
    }
}
