<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLogLogger;
use App\Services\AuditLogQuery;
use Spatie\Activitylog\Models\Activity;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->organization = Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->organization->id,
    ]);
    $this->otherOrganization = Organization::factory()->create();
});

// Query service
it('scopes activity log to the current organization', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);
    $otherCampaign = Campaign::factory()->create(['organization_id' => $this->otherOrganization->id]);

    $own = activity()
        ->performedOn($campaign)
        ->causedBy($this->user)
        ->event('updated')
        ->log('Updated campaign for audit test');

    activity()
        ->performedOn($otherCampaign)
        ->causedBy(User::factory()->create(['organization_id' => $this->otherOrganization->id]))
        ->event('updated')
        ->log('Updated other campaign for audit test');

    $ids = AuditLogQuery::forOrganization($this->organization)
        ->pluck('id')
        ->all();

    expect($ids)->toContain($own->id);
});

it('filters audit log by event', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);

    activity()->performedOn($campaign)->causedBy($this->user)->event('created')->log('Created campaign for event filter');
    $updated = activity()->performedOn($campaign)->causedBy($this->user)->event('updated')->log('Updated campaign for event filter');

    $result = AuditLogQuery::forOrganization($this->organization, ['event' => 'updated'])->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($updated->id);
});

it('filters audit log by subject type', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
    ]);

    activity()->performedOn($campaign)->causedBy($this->user)->event('updated')->log('Updated campaign for subject filter');
    activity()->performedOn($donation)->event('updated')->log('Updated donation for subject filter');

    $result = AuditLogQuery::forOrganization($this->organization, ['subject_type' => Donation::class])->get();

    expect($result->pluck('subject_type')->unique()->all())->toBe([Donation::class]);
    expect($result->pluck('subject_id'))->toContain($donation->id);
});

it('filters audit log by search term', function () {
    $campaign = Campaign::factory()->create([
        'organization_id' => $this->organization->id,
        'title' => 'Ramadan Fundraiser',
    ]);

    activity()->performedOn($campaign)->causedBy($this->user)->event('updated')->log('Updated Ramadan fundraiser');
    activity()->performedOn($campaign)->causedBy($this->user)->event('updated')->log('Updated campaign status');

    $result = AuditLogQuery::forOrganization($this->organization, ['search' => 'Ramadan'])->get();

    expect($result)->toHaveCount(1);
});

it('scopes donation and subscription activities through their campaign', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);
    $otherCampaign = Campaign::factory()->create(['organization_id' => $this->otherOrganization->id]);
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->id,
        'donor_id' => $donor->id,
    ]);
    $otherDonation = Donation::factory()->create([
        'campaign_id' => $otherCampaign->id,
        'donor_id' => $donor->id,
    ]);

    $own = activity()->performedOn($donation)->event('updated')->log('Updated donation for scope test');
    activity()->performedOn($otherDonation)->event('updated')->log('Updated other donation for scope test');

    $ids = AuditLogQuery::forOrganization($this->organization)
        ->pluck('id')
        ->all();

    expect($ids)->toContain($own->id);
});

// Page
it('renders the audit log page', function () {
    actingAs($this->user)
        ->get('https://app.example.test/audit-log')
        ->assertOk()
        ->assertSee('Audit Log');
});

it('lists organization audit log entries on the page', function () {
    $campaign = Campaign::factory()->create(['organization_id' => $this->organization->id]);

    activity()
        ->performedOn($campaign)
        ->causedBy($this->user)
        ->event('updated')
        ->log('Updated campaign');

    actingAs($this->user)
        ->get('https://app.example.test/audit-log')
        ->assertOk()
        ->assertSee('Updated campaign');
});

it('does not show other organizations activity entries', function () {
    $otherCampaign = Campaign::factory()->create(['organization_id' => $this->otherOrganization->id]);

    activity()
        ->performedOn($otherCampaign)
        ->event('updated')
        ->log('Updated other campaign');

    actingAs($this->user)
        ->get('https://app.example.test/audit-log')
        ->assertOk()
        ->assertDontSee('Updated other campaign');
});

// Element logging
it('logs element creation automatically', function () {
    $element = Element::factory()->create(['organization_id' => $this->organization->id]);

    $activity = Activity::query()
        ->where('subject_type', Element::class)
        ->where('subject_id', $element->id)
        ->where('event', 'created')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->log_name)->toBe('element');
});

it('includes element activities in organization audit log', function () {
    $element = Element::factory()->create(['organization_id' => $this->organization->id]);

    $activities = AuditLogQuery::forOrganization($this->organization)->get();

    expect($activities->pluck('subject_id')->all())->toContain($element->id);
});

// Stripe manual logging
it('logs stripe connect events manually', function () {
    AuditLogLogger::stripeConnected($this->organization, $this->user, 'acct_test123');

    $activity = Activity::query()
        ->where('subject_type', Organization::class)
        ->where('subject_id', $this->organization->id)
        ->where('event', 'stripe_connected')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->description)->toContain('Stripe Connect');
    expect($activity->getProperty('stripe_account_id'))->toBe('acct_test123');
});

it('logs stripe disconnect events manually', function () {
    AuditLogLogger::stripeDisconnected($this->organization, $this->user);

    $activity = Activity::query()
        ->where('subject_type', Organization::class)
        ->where('subject_id', $this->organization->id)
        ->where('event', 'stripe_disconnected')
        ->first();

    expect($activity)->not->toBeNull();
});

it('logs stripe onboarding completed event manually', function () {
    AuditLogLogger::stripeOnboardingCompleted($this->organization, $this->user, 'acct_test123');

    $activity = Activity::query()
        ->where('subject_type', Organization::class)
        ->where('subject_id', $this->organization->id)
        ->where('event', 'stripe_onboarding_completed')
        ->first();

    expect($activity)->not->toBeNull();
});

it('shows stripe connect manual logs in audit log page', function () {
    AuditLogLogger::stripeConnected($this->organization, $this->user, 'acct_test123');

    actingAs($this->user)
        ->get('https://app.example.test/audit-log')
        ->assertOk()
        ->assertSee('Stripe Connect');
});
