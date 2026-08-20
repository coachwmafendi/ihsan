<?php

declare(strict_types=1);

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Services\DonationActivityLogger;
use Spatie\Activitylog\Models\Activity;

function donationLoggedAsDonorPortal(string $realSource): Donation
{
    $campaign = Campaign::factory()->for(Organization::factory())->create();
    $donation = Donation::factory()->for($campaign)->create(['source' => $realSource]);

    DonationActivityLogger::created($donation, null, [
        'initiator' => 'donor',
        'source' => 'donor_portal',
    ]);

    return $donation;
}

it('relabels a donation entry with the source the donation actually has', function () {
    $donation = donationLoggedAsDonorPortal('element');

    test()->artisan('app:backfill-audit-log-event-source')->assertExitCode(0);

    $activity = Activity::query()->where('subject_id', $donation->getKey())->latest('id')->first();

    expect($activity->properties->get('source'))->toBe('element')
        // The rest of the context has to survive the rewrite.
        ->and($activity->properties->get('initiator'))->toBe('donor');
});

it('writes nothing on a dry run', function () {
    $donation = donationLoggedAsDonorPortal('campaign_page');

    test()->artisan('app:backfill-audit-log-event-source', ['--dry-run' => true])->assertExitCode(0);

    expect(Activity::query()->where('subject_id', $donation->getKey())->latest('id')->first()
        ->properties->get('source'))->toBe('donor_portal');
});

it('leaves entries alone once they are correct', function () {
    donationLoggedAsDonorPortal('element');

    test()->artisan('app:backfill-audit-log-event-source')->assertExitCode(0);

    test()->artisan('app:backfill-audit-log-event-source')
        ->expectsOutputToContain('No mislabelled donation entries found.')
        ->assertExitCode(0);
});
