<?php

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogQuery;
use App\Services\DonationActivityLogger;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->campaign = Campaign::factory()->for($this->organization)->create();
    $this->donor = Donor::factory()->create();
    $this->donation = Donation::factory()->create([
        'campaign_id' => $this->campaign->id,
        'donor_id' => $this->donor->id,
    ]);
});

it('filters audit log by system initiator', function () {
    $activity = DonationActivityLogger::created($this->donation, null, ['initiator' => 'system', 'source' => 'webhook']);

    $results = AuditLogQuery::forOrganization($this->organization, ['initiator' => 'system'])->get();

    expect($results->pluck('id'))->toContain($activity->id);
});

it('filters audit log by donor initiator', function () {
    $activity = DonationActivityLogger::created($this->donation, null, ['initiator' => 'donor', 'source' => 'donor_portal']);

    $results = AuditLogQuery::forOrganization($this->organization, ['initiator' => 'donor'])->get();

    expect($results->pluck('id'))->toContain($activity->id);
});

it('filters audit log by admin initiator', function () {
    $admin = User::factory()->for($this->organization)->create();

    $activity = DonationActivityLogger::refunded($this->donation, 50.00, $admin, ['source' => 'manual']);

    $results = AuditLogQuery::forOrganization($this->organization, ['initiator' => 'admin'])->get();

    expect($results->pluck('id'))->toContain($activity->id);
});

it('includes options for new donation and subscription events', function () {
    $events = AuditLogQuery::eventOptions();

    expect($events)->toHaveKeys([
        'donation.created',
        'transaction_attempt_succeeded',
        'donation.refunded',
        'subscription.created',
        'installment.charged',
    ]);
});
