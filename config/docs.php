<?php

declare(strict_types=1);

return [
    'path' => resource_path('docs'),

    'nav' => [
        [
            'slug' => 'getting-started',
            'children' => [
                ['slug' => 'index'],
                ['slug' => 'installation'],
                ['slug' => 'payment-methods'],
                ['slug' => 'test-mode'],
                ['slug' => 'organizations-and-accounts'],
            ],
        ],
        [
            'slug' => 'run-campaigns',
            'children' => [
                ['slug' => 'index'],
                ['slug' => 'campaign-setup'],
                ['slug' => 'campaign-page'],
                ['slug' => 'checkout-modal'],
                ['slug' => 'elements'],
                ['slug' => 'virtual-terminal'],
            ],
        ],
        [
            'slug' => 'engage-supporters',
            'children' => [
                ['slug' => 'index'],
                ['slug' => 'donor-portal'],
                ['slug' => 'emails'],
                ['slug' => 'subscriptions'],
                ['slug' => 'upgrade-links'],
            ],
        ],
        [
            'slug' => 'analyze-results',
            'children' => [
                ['slug' => 'index'],
                ['slug' => 'dashboard'],
                ['slug' => 'insights'],
                ['slug' => 'donations'],
                ['slug' => 'supporters'],
                ['slug' => 'data-export'],
            ],
        ],
        [
            'slug' => 'build-integrate',
            'children' => [
                ['slug' => 'index'],
                ['slug' => 'embed-widget'],
                ['slug' => 'javascript-api'],
                ['slug' => 'webhooks'],
                ['slug' => 'rest-api'],
            ],
        ],
    ],

    'landing' => [
        'hero_image' => '/images/docs/app-dashboard.png',
        'cards' => [
            'getting-started' => [
                'image' => '/images/docs/register-organization.png',
            ],
            'run-campaigns' => [
                'image' => '/images/docs/app-campaigns.png',
            ],
            'engage-supporters' => [
                'image' => '/images/docs/app-supporters.png',
            ],
            'analyze-results' => [
                'image' => '/images/docs/app-donations.png',
            ],
            'build-integrate' => [
                'image' => '/images/docs/app-elements.png',
            ],
        ],
    ],
];
