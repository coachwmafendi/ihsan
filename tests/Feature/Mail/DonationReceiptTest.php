<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Mail\DonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;

it('builds donation receipt mailable with correct subject', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'John', 'email' => 'john@test.com']);
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'donor_id' => $donor->getKey(),
        'gross_amount' => 100,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $mailable = new DonationReceipt($donation);

    $mailable->assertHasSubject('Your Donation Receipt — '.config('app.name'));
    $mailable->assertSeeInHtml('RM 100');
    $mailable->assertSeeInHtml('Test Campaign');
    $mailable->assertSeeInHtml('Thank you');
});
