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
        'donor_fee_covered' => 0,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $mailable = new DonationReceipt($donation);

    $mailable->assertHasSubject('Your Donation Receipt — Test Org');
    $mailable->assertSeeInHtml('RM 100');
    $mailable->assertSeeInHtml('Test Campaign');
    $mailable->assertSeeInHtml('Thank you');
});

it('shows fee breakdown in donor receipt when donor covered fees', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'John', 'email' => 'john@test.com']);
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'donor_id' => $donor->getKey(),
        'gross_amount' => 100.00,
        'donor_fee_covered' => 3.20,
        'net_amount' => 96.80,
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::OneTime,
    ]);

    $mailable = new DonationReceipt($donation);

    $mailable->assertSeeInHtml('Donation');
    $mailable->assertSeeInHtml('Processing Fee');
    $mailable->assertSeeInHtml('Total Charged');
    $mailable->assertSeeInHtml('100.00'); // donation amount
    $mailable->assertSeeInHtml('3.20');   // fee
    $mailable->assertSeeInHtml('103.20'); // total charged
});

it('includes organization contact footer in donor receipt', function () {
    $organization = Organization::factory()->create([
        'name' => 'Maahad Tahfiz Muntazatut Taqwa',
        'address_line_1' => 'Lot 3234, Jalan Dengkil',
        'city' => 'Banting',
        'state' => 'Selangor',
        'postcode' => '42700',
        'country' => 'Malaysia',
        'contact_phone' => '+60123244895',
        'contact_email' => 'maahad@example.com',
        'website_url' => 'https://maahad.example.com',
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $mailable = new DonationReceipt($donation);
    $mailable->assertSeeInHtml('Maahad Tahfiz Muntazatut Taqwa');
    $mailable->assertSeeInHtml('Lot 3234, Jalan Dengkil, Banting, Selangor, 42700, Malaysia');
    $mailable->assertSeeInHtml('+60123244895');
    $mailable->assertSeeInHtml('maahad@example.com');
    $mailable->assertSeeInHtml('https://maahad.example.com');
});

it('renders receipt in donor locale when set to malay', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['locale' => 'ms', 'name' => 'Ahmad']);
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $mailable = new DonationReceipt($donation);

    $mailable->assertHasSubject('Resit Derma Anda — Test Org');
    $mailable->assertSeeInHtml('Terima kasih atas derma anda!');
    $mailable->assertSeeInHtml('Hi Ahmad');
    $mailable->assertSeeInHtml('Organisasi');
});
