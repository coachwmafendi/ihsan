<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('SES_ACCESS_KEY_ID') ?: env('AWS_ACCESS_KEY_ID'),
        'secret' => env('SES_SECRET_ACCESS_KEY') ?: env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('SES_REGION') ?: (env('AWS_DEFAULT_REGION') === 'auto' ? 'us-east-1' : env('AWS_DEFAULT_REGION', 'us-east-1')),
        'webhook_token' => env('SES_WEBHOOK_TOKEN'),
        'topic_arn' => env('SES_WEBHOOK_TOPIC_ARN'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'meta' => [
        'api_version' => env('META_GRAPH_API_VERSION', 'v21.0'),
    ],

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
        'processing_fee_percent' => (float) env('PAYMENT_PROCESSING_FEE_PERCENT', 2.5),
    ],

    'billing' => [
        'default_fee_collection_method' => env('DEFAULT_FEE_COLLECTION_METHOD', 'upfront'),
    ],

    'chip' => [
        /*
         * CHIP's fee rates in DonationFeeEstimator were carried over as
         * estimates and never measured against a settled transaction, because
         * no donation has run through CHIP yet. Until they are confirmed
         * against CHIP's contracted rates, campaigns cannot be moved onto it:
         * the donor fee cover would be wrong from the first donation. Existing
         * CHIP campaigns, if any, keep working.
         */
        'donations_enabled' => (bool) env('CHIP_DONATIONS_ENABLED', false),

        'processing_fee_percent' => (float) env('CHIP_PROCESSING_FEE_PERCENT', 2.5),
        'fpx_processing_fee_percent' => (float) env('CHIP_FPX_PLATFORM_FEE_PERCENT', 1.5),
        'fpx_fee_type' => env('CHIP_FPX_FEE_TYPE', 'fixed'),
        'fpx_fee_amount' => (int) env('CHIP_FPX_FEE_AMOUNT', 150),
    ],

    'maxmind' => [
        'license_key' => env('MAXMIND_LICENSE_KEY'),
        'database_path' => env('MAXMIND_DATABASE_PATH', storage_path('app/maxmind/GeoLite2-City.mmdb')),
    ],

    'cloudflare' => [
        /*
         * Cloudflare Web Analytics sets no cookies and cannot identify anyone,
         * so unlike our tag manager it is safe to run on donor-facing pages —
         * it tells us whether donation forms are slow or erroring.
         */
        'analytics_token' => env('CLOUDFLARE_ANALYTICS_TOKEN'),
    ],

    'google' => [
        /*
         * Ihsan's own Google Tag Manager container. Deliberately absent from
         * donor-facing pages: donation forms already carry each NGO's own
         * pixels, and those donors belong to the NGO, not to us.
         */
        'gtm_id' => env('GOOGLE_TAG_MANAGER_ID'),
    ],

    'recurring' => [
        'use_app_controlled' => filter_var(env('RECURRING_USE_APP_CONTROLLED', true), FILTER_VALIDATE_BOOLEAN),
        'retry_intervals_days' => [1, 3, 7, 7],
        'max_failed_installments' => 4,
    ],

];
