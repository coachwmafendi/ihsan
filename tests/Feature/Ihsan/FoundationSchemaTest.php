<?php

use Illuminate\Support\Facades\Schema;

it('has the ihsan foundation tables', function () {
    foreach ([
        'organizations',
        'organization_documents',
        'campaigns',
        'donors',
        'donations',
        'subscriptions',
        'elements',
        'platform_fees',
        'webhook_logs',
    ] as $table) {
        expect(Schema::hasTable($table))->toBeTrue("Missing table [{$table}]");
    }
});

it('adds tenant and role columns to users', function () {
    expect(Schema::hasColumns('users', [
        'organization_id',
        'role',
    ]))->toBeTrue();
});

it('has key columns for organization-scoped fundraising', function () {
    expect(Schema::hasColumns('campaigns', [
        'organization_id',
        'title',
        'target_amount',
        'collected_amount',
        'allow_recurring',
        'status',
        'suggested_amounts',
    ]))->toBeTrue();

    expect(Schema::hasColumns('donations', [
        'campaign_id',
        'donor_id',
        'subscription_id',
        'gross_amount',
        'platform_fee',
        'net_amount',
        'status',
        'type',
        'utm_params',
    ]))->toBeTrue();
});
