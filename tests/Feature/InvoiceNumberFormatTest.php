<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;

it('generates a receipt number in the RECEIPT format when a donation is created', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
        'invoice_number' => null,
    ]);

    $donation->refresh();

    $year = $donation->created_at->format('Y');

    expect($donation->invoice_number)
        ->toBe("RECEIPT-{$organization->public_id}-{$year}-{$donation->id}");
});

it('does not overwrite an existing invoice number', function () {
    $donation = Donation::factory()->create([
        'invoice_number' => 'MANUAL-001',
    ]);

    expect($donation->fresh()->invoice_number)->toBe('MANUAL-001');
});
