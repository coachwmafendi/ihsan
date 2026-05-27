<?php

use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;

it('logs in with valid magic token', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'valid-token-123',
        'magic_token_expires_at' => now()->addHours(24),
    ]);

    $this->get(route('donorportal.magic-login', ['token' => 'valid-token-123']))
        ->assertRedirect(route('donorportal.dashboard'));

    $this->assertEquals(session('donor_id'), $donor->getKey());
});

it('rejects expired magic token', function () {
    Donor::factory()->create([
        'magic_token' => 'expired-token',
        'magic_token_expires_at' => now()->subHour(),
    ]);

    $this->get(route('donorportal.magic-login', ['token' => 'expired-token']))
        ->assertRedirect(route('donorportal.login'));
});

it('requires valid session for donor portal pages', function () {
    $this->get(route('donorportal.donations'))->assertRedirect(route('donorportal.login'));
    $this->get(route('donorportal.subscriptions'))->assertRedirect(route('donorportal.login'));
});

it('shows donation history for authenticated donor', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations'))
        ->assertOk()
        ->assertSee('Ihsan.')
        ->assertSee('Dashboard')
        ->assertSee('Donations')
        ->assertSee('Subscriptions');
});

it('shows subscriptions for authenticated donor', function () {
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.subscriptions'))
        ->assertOk()
        ->assertSee('Ihsan.')
        ->assertSee('Subscriptions');
});

it('logs out donor and redirects to login', function () {
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey()])
        ->post(route('donorportal.logout'))
        ->assertRedirect(route('donorportal.login'));

    $this->assertNull(session('donor_id'));
});

it('renders login page with new design', function () {
    $this->get(route('donorportal.login'))
        ->assertOk()
        ->assertSee('logo-ihsan.png', false)
        ->assertSee('Your giving, your way.')
        ->assertSee('Send Login Link');
});

it('renders dashboard with stats and activity sections', function () {
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.dashboard'))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Total Given')
        ->assertSee('Active Plans')
        ->assertSee('Monthly')
        ->assertSee('Monthly Giving')
        ->assertSee('By Campaign')
        ->assertSee('Recent Activity');
});

it('renders donations page with stats and card list', function () {
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations'))
        ->assertOk()
        ->assertSee('Total Given')
        ->assertSee('Donations')
        ->assertSee('Your complete giving history.');
});

it('renders subscriptions page with manage subtitle', function () {
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.subscriptions'))
        ->assertOk()
        ->assertSee('Subscriptions')
        ->assertSee('Manage your recurring donations.');
});

it('allows donor to download receipt for their own succeeded donation', function () {
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations.receipt.download', $donation))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('denies receipt download for non-succeeded donation', function () {
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'status' => DonationStatus::Pending,
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations.receipt.download', $donation))
        ->assertNotFound();
});

it('denies receipt download for another donors donation', function () {
    $donor = Donor::factory()->create();
    $otherDonor = Donor::factory()->create();
    $donation = Donation::factory()->create([
        'donor_id' => $otherDonor->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations.receipt.download', $donation))
        ->assertNotFound();
});

it('denies receipt download for unauthenticated user', function () {
    $donation = Donation::factory()->create([
        'status' => DonationStatus::Succeeded,
    ]);

    $this->get(route('donorportal.donations.receipt.download', $donation))
        ->assertNotFound();
});

it('shows receipt download button for succeeded donations in donor portal', function () {
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations'))
        ->assertOk()
        ->assertSee('Receipt')
        ->assertSee(route('donorportal.donations.receipt.download', $donation), false);
});

it('hides receipt download button for pending donations in donor portal', function () {
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'status' => DonationStatus::Pending,
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations'))
        ->assertOk()
        ->assertDontSee('Receipt');
});

it('filters donations page by subscription id', function () {
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
    ]);
    $relatedDonation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'subscription_id' => $subscription->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);
    $otherDonation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'subscription_id' => null,
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.donations', ['subscription' => $subscription->getKey()]))
        ->assertOk()
        ->assertSee('Payment history for')
        ->assertSee($relatedDonation->campaign->title)
        ->assertDontSee($otherDonation->campaign->title);
});

it('shows history button for subscriptions in donor portal', function () {
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'status' => SubscriptionStatus::Active,
    ]);

    $this->withSession(['donor_id' => $donor->getKey()])
        ->get(route('donorportal.subscriptions'))
        ->assertOk()
        ->assertSee('History')
        ->assertSee(route('donorportal.donations', ['subscription' => $subscription->getKey()]), false);
});
