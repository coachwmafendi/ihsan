<?php

return [
    'nav' => [
        'docs' => 'Docs',
        'documentation_home' => 'Documentation home',
        'browse_by_topic' => 'Browse by topic',

        'getting-started' => [
            'label' => 'Getting started',
            'children' => [
                'index' => 'Overview',
                'installation' => 'Installation',
                'payment-methods' => 'Payment methods',
                'test-mode' => 'Test mode',
                'organizations-and-accounts' => 'Organizations and accounts',
            ],
        ],

        'run-campaigns' => [
            'label' => 'Run campaigns',
            'children' => [
                'index' => 'Overview',
                'campaign-setup' => 'Campaign setup',
                'campaign-page' => 'Campaign page',
                'checkout-modal' => 'Checkout modal',
                'elements' => 'Elements',
                'virtual-terminal' => 'Virtual terminal',
            ],
        ],

        'engage-supporters' => [
            'label' => 'Engage supporters',
            'children' => [
                'index' => 'Overview',
                'donor-portal' => 'Donor portal',
                'emails' => 'Emails',
                'subscriptions' => 'Subscriptions',
                'upgrade-links' => 'Upgrade links',
            ],
        ],

        'analyze-results' => [
            'label' => 'Analyze results',
            'children' => [
                'index' => 'Overview',
                'dashboard' => 'Dashboard',
                'insights' => 'Insights',
                'donations' => 'Donations',
                'supporters' => 'Supporters',
                'data-export' => 'Data export',
            ],
        ],

        'build-integrate' => [
            'label' => 'Build & integrate',
            'children' => [
                'index' => 'Overview',
                'embed-widget' => 'Embed widget',
                'javascript-api' => 'JavaScript API',
                'webhooks' => 'Webhooks',
                'rest-api' => 'REST API',
            ],
        ],
    ],

    'landing' => [
        'hero_alt' => 'Ihsan admin dashboard',
        'cards' => [
            'getting-started' => [
                'title' => 'Getting started',
                'description' => 'Set up your organization, connect payments, and install your first donation form.',
            ],
            'run-campaigns' => [
                'title' => 'Run campaigns',
                'description' => 'Create campaigns, publish pages, and accept donations through any channel.',
            ],
            'engage-supporters' => [
                'title' => 'Engage supporters',
                'description' => 'Manage donor portal, email receipts, subscriptions, and upgrade links.',
            ],
            'analyze-results' => [
                'title' => 'Analyze results',
                'description' => 'Track donations, understand recurring revenue, and export your data.',
            ],
            'build-integrate' => [
                'title' => 'Build & integrate',
                'description' => 'Integrate the embed widget, JavaScript API, webhooks, and REST API.',
            ],
        ],
    ],
];
