<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\UserRole;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\URL;

it('downloads receipt with a valid signed url', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
        'invoice_number' => 'INV-REC-001',
    ]);

    $url = URL::signedRoute('receipts.signed', ['donation' => $donation]);

    $response = $this->get($url);

    $response
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('INV-REC-001');
});

it('downloads receipt for one-time donations', function () {
    $donation = Donation::factory()->create([
        'type' => DonationType::OneTime,
        'status' => DonationStatus::Succeeded,
        'invoice_number' => 'INV-ONE-002',
    ]);

    $url = URL::signedRoute('receipts.signed', ['donation' => $donation]);

    $this->get($url)
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('redirects an invalid signed url to the donor portal login', function () {
    $donation = Donation::factory()->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    $this->get('/receipts/'.$donation->public_id.'?signature=invalid')
        ->assertRedirect(route('donorportal.login', $donation->campaign->organization));
});

it('redirects an expired signed url to the donor portal login', function () {
    $donation = Donation::factory()->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    $url = URL::signedRoute('receipts.signed', ['donation' => $donation], now()->subMinute());

    $this->get($url)
        ->assertRedirect(route('donorportal.login', $donation->campaign->organization));
});

it('returns 404 for non-succeeded donations via signed url', function () {
    $donation = Donation::factory()->create([
        'status' => DonationStatus::Pending,
        'type' => DonationType::Recurring,
    ]);

    $url = URL::signedRoute('receipts.signed', ['donation' => $donation]);

    $this->get($url)
        ->assertNotFound();
});

it('downloads receipt with a valid token', function () {
    $donation = Donation::factory()->create([
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
        'invoice_number' => 'INV-TOK-001',
    ]);

    $url = route('receipts.token', ['donation' => $donation, 'token' => $donation->receipt_token]);

    $response = $this->get($url);

    $response
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');

    expect($response->headers->get('content-disposition'))->toContain('INV-TOK-001');
});

it('rejects an invalid token', function () {
    $donation = Donation::factory()->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    $url = route('receipts.token', ['donation' => $donation, 'token' => 'invalid-token']);

    $this->get($url)
        ->assertNotFound();
});

it('redirects to the donor portal login when the token is missing', function () {
    $donation = Donation::factory()->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    $this->get('/receipts/'.$donation->public_id)
        ->assertRedirect(route('donorportal.login', $donation->campaign->organization));
});

it('downloads receipt via authenticated donation receipt route', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => DonationStatus::Succeeded,
        'invoice_number' => 'INV-AUTH-001',
    ]);

    $this->actingAs($user)
        ->get('/donations/'.$donation->public_id.'/receipt')
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('returns 404 for non-succeeded donations via authenticated donation receipt route', function (DonationStatus $status) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'status' => $status,
    ]);

    $this->actingAs($user)
        ->get('/donations/'.$donation->public_id.'/receipt')
        ->assertNotFound();
})->with([
    DonationStatus::Pending,
    DonationStatus::Failed,
    DonationStatus::Refunded,
    DonationStatus::Cancelled,
]);
