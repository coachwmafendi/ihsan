<?php

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
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

it('rejects an invalid signed url', function () {
    $donation = Donation::factory()->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    $this->get('/receipts/'.$donation->public_id.'?signature=invalid')
        ->assertForbidden();
});

it('rejects an expired signed url', function () {
    $donation = Donation::factory()->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    $url = URL::signedRoute('receipts.signed', ['donation' => $donation], now()->subMinute());

    $this->get($url)
        ->assertForbidden();
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

it('rejects a request when token is missing', function () {
    $donation = Donation::factory()->create([
        'status' => DonationStatus::Succeeded,
        'type' => DonationType::Recurring,
    ]);

    $this->get('/receipts/'.$donation->public_id)
        ->assertForbidden();
});
