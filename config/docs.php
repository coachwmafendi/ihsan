<?php

declare(strict_types=1);

return [
    'path' => resource_path('docs'),

    'nav' => [
        [
            'label' => 'Getting started',
            'slug' => 'getting-started',
            'children' => [
                ['label' => 'Overview', 'slug' => 'index'],
                ['label' => 'Installation', 'slug' => 'installation'],
                ['label' => 'Payment methods', 'slug' => 'payment-methods'],
                ['label' => 'Test mode', 'slug' => 'test-mode'],
                ['label' => 'Organizations and accounts', 'slug' => 'organizations-and-accounts'],
            ],
        ],
        [
            'label' => 'Run campaigns',
            'slug' => 'run-campaigns',
            'children' => [
                ['label' => 'Overview', 'slug' => 'index'],
                ['label' => 'Campaign setup', 'slug' => 'campaign-setup'],
                ['label' => 'Campaign page', 'slug' => 'campaign-page'],
                ['label' => 'Checkout modal', 'slug' => 'checkout-modal'],
                ['label' => 'Elements', 'slug' => 'elements'],
                ['label' => 'Virtual terminal', 'slug' => 'virtual-terminal'],
            ],
        ],
        [
            'label' => 'Engage supporters',
            'slug' => 'engage-supporters',
            'children' => [
                ['label' => 'Overview', 'slug' => 'index'],
                ['label' => 'Donor portal', 'slug' => 'donor-portal'],
                ['label' => 'Emails', 'slug' => 'emails'],
                ['label' => 'Subscriptions', 'slug' => 'subscriptions'],
                ['label' => 'Upgrade links', 'slug' => 'upgrade-links'],
            ],
        ],
        [
            'label' => 'Analyze results',
            'slug' => 'analyze-results',
            'children' => [
                ['label' => 'Overview', 'slug' => 'index'],
                ['label' => 'Dashboard', 'slug' => 'dashboard'],
                ['label' => 'Insights', 'slug' => 'insights'],
                ['label' => 'Donations', 'slug' => 'donations'],
                ['label' => 'Supporters', 'slug' => 'supporters'],
                ['label' => 'Data export', 'slug' => 'data-export'],
            ],
        ],
        [
            'label' => 'Build & integrate',
            'slug' => 'build-integrate',
            'children' => [
                ['label' => 'Overview', 'slug' => 'index'],
                ['label' => 'Embed widget', 'slug' => 'embed-widget'],
                ['label' => 'JavaScript API', 'slug' => 'javascript-api'],
                ['label' => 'Webhooks', 'slug' => 'webhooks'],
                ['label' => 'REST API', 'slug' => 'rest-api'],
            ],
        ],
    ],

    'landing' => [
        'hero_image' => '/images/docs/app-dashboard.png',
        'hero_alt' => 'Ihsan admin dashboard',
        'cards' => [
            'getting-started' => [
                'image' => '/images/docs/register-organization.png',
                'description' => 'Set up your organization, connect payments, and install your first donation form.',
            ],
            'run-campaigns' => [
                'image' => '/images/docs/app-campaigns.png',
                'description' => 'Create campaigns, publish pages, and accept donations through any channel.',
            ],
            'engage-supporters' => [
                'image' => '/images/docs/app-supporters.png',
                'description' => 'Manage donor portal, email receipts, subscriptions, and upgrade links.',
            ],
            'analyze-results' => [
                'image' => '/images/docs/app-donations.png',
                'description' => 'Track donations, understand recurring revenue, and export your data.',
            ],
            'build-integrate' => [
                'image' => '/images/docs/app-elements.png',
                'description' => 'Integrate the embed widget, JavaScript API, webhooks, and REST API.',
            ],
        ],
    ],
];
