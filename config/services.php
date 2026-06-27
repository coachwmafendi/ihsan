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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
        'processing_fee_percent' => (float) env('PAYMENT_PROCESSING_FEE_PERCENT', 2.5),
    ],

    'chip' => [
        'api_base_url' => env('CHIP_API_BASE_URL', 'https://gate.chip-in.asia/api/v1'),
        'processing_fee_percent' => (float) env('CHIP_PROCESSING_FEE_PERCENT', 2.5),
        'fpx_fee_type' => env('CHIP_FPX_FEE_TYPE', 'fixed'),
        'fpx_fee_amount' => (int) env('CHIP_FPX_FEE_AMOUNT', 150),
    ],

    'recurring' => [
        'use_app_controlled' => filter_var(env('RECURRING_USE_APP_CONTROLLED', true), FILTER_VALIDATE_BOOLEAN),
        'retry_intervals_days' => [1, 3, 7, 7],
        'max_failed_installments' => 6,
    ],

];
