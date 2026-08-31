<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Jobs\SendCampaignMilestoneNotification;
use App\Mail\CampaignMilestoneNotification;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

/**
 * target_amount and collected_amount are stored in the campaign's own
 * currency, so an email quoting them has to name that currency rather than
 * assuming MYR.
 */
beforeEach(function () {
    $this->organization = Organization::factory()->create([
        'settings' => ['notify_campaign_milestone' => true],
    ]);

    User::factory()->create([
        'organization_id' => $this->organization->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);
});

it('quotes a campaign in the currency it collects in', function () {
    $campaign = Campaign::factory()->for($this->organization)->create([
        'has_target' => true,
        'target_amount' => 10000.00,
        'collected_amount' => 5000.00,
        'config' => ['default_currency' => 'SGD'],
    ]);

    expect($campaign->defaultCurrency())->toBe('SGD');

    Mail::fake();
    (new SendCampaignMilestoneNotification($campaign, 0.0))->handle();

    Mail::assertQueued(CampaignMilestoneNotification::class, function (CampaignMilestoneNotification $mail): bool {
        return $mail->currency === 'SGD' && $mail->collected === '5,000.00';
    });
});

it('falls back to the organization currency, then to MYR', function () {
    $withOrgCurrency = Campaign::factory()->for(
        Organization::factory()->create(['settings' => ['default_currency' => 'sgd']])
    )->create(['config' => []]);

    $withNothing = Campaign::factory()->for(
        Organization::factory()->create(['settings' => []])
    )->create(['config' => []]);

    expect($withOrgCurrency->defaultCurrency())->toBe('SGD')
        ->and($withNothing->defaultCurrency())->toBe('MYR');
});
