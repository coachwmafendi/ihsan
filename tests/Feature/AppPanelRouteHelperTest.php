<?php

use App\Mail\NewDonationNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('builds app routes on the app panel domain', function () {
    expect(appPanelRoute('app.dashboard'))
        ->toBe('https://app.example.test/dashboard');
});

it('falls back to the app url when no app panel domain is configured', function () {
    config(['app.app_panel_domain' => null]);

    expect(appPanelRoute('app.dashboard'))
        ->toBe(rtrim(config('app.url'), '/').'/dashboard');
});

it('is used for admin notification email buttons', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create();

    $html = (new NewDonationNotification($donation, 'MYR 100.00'))->render();

    expect($html)->toContain('https://app.example.test/donations/'.$donation->public_id);
});
