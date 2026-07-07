<?php

use App\Actions\Stripe\ManageStripeSubscription;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Mail\SubscriptionAmountChangedNotification;
use App\Mail\SupporterSubscriptionAmountChangedNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;

it('resolves org-scoped donor portal login page', function () {
    $org = Organization::factory()->create();

    $this->get(route('donorportal.login', $org))
        ->assertOk();
});

it('returns 404 for unknown org code in donor portal', function () {
    $this->get('/donorportal/NOTEXIST/login')
        ->assertNotFound();
});

it('logs in with valid magic token', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create([
        'magic_token' => hash('sha256', 'valid-token-123'),
        'magic_token_expires_at' => now()->addHours(24),
    ]);

    $this->get(route('donorportal.magic-login', ['organization' => $org, 'token' => 'valid-token-123']))
        ->assertRedirect(route('donorportal.dashboard', $org));

    $this->assertEquals(session('donor_id'), $donor->getKey());
    $this->assertEquals(session('organization_id'), $org->getKey());
});

it('rejects expired magic token', function () {
    $org = Organization::factory()->create();
    Donor::factory()->create([
        'magic_token' => hash('sha256', 'expired-token'),
        'magic_token_expires_at' => now()->subHour(),
    ]);

    $this->get(route('donorportal.magic-login', ['organization' => $org, 'token' => 'expired-token']))
        ->assertRedirect(route('donorportal.login', $org));
});

it('requires valid session for donor portal pages', function () {
    $org = Organization::factory()->create();

    $this->get(route('donorportal.donations', $org))->assertRedirect(route('donorportal.login', $org));
    $this->get(route('donorportal.subscriptions', $org))->assertRedirect(route('donorportal.login', $org));
});

it('rejects sessions from another organization', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $otherOrg->getKey()])
        ->get(route('donorportal.dashboard', $org))
        ->assertRedirect(route('donorportal.login', $org));
});

it('shows donation history for authenticated donor', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', $org))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Donations')
        ->assertSee('Subscriptions');
});

it('shows subscriptions for authenticated donor', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create([
        'magic_token' => 'test-token',
        'magic_token_expires_at' => now()->addDay(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.subscriptions', $org))
        ->assertOk()
        ->assertSee('Subscriptions');
});

it('logs out donor and redirects to login', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.logout', $org))
        ->assertRedirect(route('donorportal.login', $org));

    $this->assertNull(session('donor_id'));
    $this->assertNull(session('organization_id'));
});

it('renders login page with new design', function () {
    $org = Organization::factory()->create();

    $this->get(route('donorportal.login', $org))
        ->assertOk()
        ->assertSee($org->name)
        ->assertSee('Donor Portal')
        ->assertSee('Send Login Link');
});

it('renders dashboard with stats and activity sections', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.dashboard', $org))
        ->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Total Given')
        ->assertSee('Active Plans')
        ->assertSee('Monthly')
        ->assertSee('Giving by Campaign')
        ->assertSee('Recent Activity');
});

it('opens new donation modal with a form element token when one exists for the latest campaign', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create([
        'organization_id' => $org->getKey(),
        'checkout_modal_enabled' => true,
    ]);
    $buttonElement = Element::factory()->create([
        'organization_id' => $org->getKey(),
        'campaign_id' => $campaign->getKey(),
        'type' => ElementType::Button,
        'token' => 'BTN123',
    ]);
    $formElement = Element::factory()->create([
        'organization_id' => $org->getKey(),
        'campaign_id' => $campaign->getKey(),
        'type' => ElementType::Form,
        'token' => 'FORM123',
    ]);

    Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.dashboard', $org))
        ->assertOk()
        ->assertSee(route('donations.show', ['element' => $formElement, 'popup' => 1]), false)
        ->assertSee(route('donations.show', $formElement), false)
        ->assertDontSee(route('donations.show', ['element' => $buttonElement, 'popup' => 1]), false)
        ->assertDontSee(route('donations.campaign-show', ['campaign' => $campaign, 'popup' => 1]), false);
});

it('falls back to a campaign modal url when no active element exists for the latest campaign', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create([
        'organization_id' => $org->getKey(),
        'checkout_modal_enabled' => true,
    ]);

    Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.dashboard', $org))
        ->assertOk()
        ->assertSee(route('donations.campaign-show', ['campaign' => $campaign, 'popup' => 1]), false)
        ->assertSee(route('donations.campaign-show', $campaign), false);
});

it('renders donations page with stats and card list', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', $org))
        ->assertOk()
        ->assertSee('Total Given')
        ->assertSee('Donations')
        ->assertSee('Your complete giving history.');
});

it('renders subscriptions page with manage subtitle', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.subscriptions', $org))
        ->assertOk()
        ->assertSee('Subscriptions')
        ->assertSee('Manage your recurring donations.');
});

it('hides donations from other organizations', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $orgCampaign = Campaign::factory()->create([
        'organization_id' => $org->getKey(),
        'title' => 'Org Scoped Campaign',
    ]);
    $otherCampaign = Campaign::factory()->create([
        'organization_id' => $otherOrg->getKey(),
        'title' => 'Other Org Campaign',
    ]);

    Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $orgCampaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);
    Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $otherCampaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', $org))
        ->assertOk()
        ->assertSee($orgCampaign->title)
        ->assertDontSee($otherCampaign->title);
});

it('allows donor to download receipt for their own succeeded donation', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create([
        'organization_id' => $org->getKey(),
        'title' => 'Subscription Filter Campaign',
    ]);
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations.receipt.download', ['organization' => $org, 'donation' => $donation]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('denies receipt download for non-succeeded donation', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Pending,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations.receipt.download', ['organization' => $org, 'donation' => $donation]))
        ->assertNotFound();
});

it('denies receipt download for another donors donation', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $otherDonor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $donation = Donation::factory()->create([
        'donor_id' => $otherDonor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations.receipt.download', ['organization' => $org, 'donation' => $donation]))
        ->assertNotFound();
});

it('denies receipt download for another organizations donation', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $otherOrg->getKey()]);
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations.receipt.download', ['organization' => $org, 'donation' => $donation]))
        ->assertNotFound();
});

it('denies receipt download for unauthenticated user', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $donation = Donation::factory()->create([
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->get(route('donorportal.donations.receipt.download', ['organization' => $org, 'donation' => $donation]))
        ->assertRedirect(route('donorportal.login', $org));
});

it('shows receipt download button for succeeded donations in donor portal', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', $org))
        ->assertOk()
        ->assertSee('Receipt')
        ->assertSee(route('donorportal.donations.receipt.download', ['organization' => $org, 'donation' => $donation]), false);
});

it('hides receipt download button for pending donations in donor portal', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);

    Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Pending,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', $org))
        ->assertOk()
        ->assertDontSee('Receipt');
});

it('renders redesigned donations table with amount, date, payment method and action columns', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
        'payment_method_brand' => 'visa',
        'payment_method_last4' => '4242',
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', $org))
        ->assertOk()
        ->assertSee('Amount')
        ->assertSee('Date')
        ->assertSee('Payment method')
        ->assertSee('Action')
        ->assertSee($donation->formatted_amount)
        ->assertSee('Visa **** 4242')
        ->assertSee(route('donorportal.donations.receipt.download', ['organization' => $org, 'donation' => $donation]), false);
});

it('shows donation detail slide-over content', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $donation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 150.00,
        'donor_fee_covered' => 5.00,
        'processing_fee' => 4.50,
        'net_amount' => 150.50,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations.detail', ['organization' => $org, 'donation' => $donation]))
        ->assertOk()
        ->assertSee($donation->formatted_amount)
        ->assertSee($campaign->title)
        ->assertSee('Donation amount')
        ->assertSee('Payment breakdown')
        ->assertSee('Net received');
});

it('denies donation detail for another donors donation', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $otherDonor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $donation = Donation::factory()->create([
        'donor_id' => $otherDonor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations.detail', ['organization' => $org, 'donation' => $donation]))
        ->assertNotFound();
});

it('filters donations by status and amount range', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);

    Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Succeeded,
        'gross_amount' => 50.00,
    ]);
    Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => DonationStatus::Failed,
        'gross_amount' => 500.00,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', [
            'organization' => $org,
            'status' => DonationStatus::Succeeded->value,
            'amount_min' => 10,
            'amount_max' => 100,
        ]))
        ->assertOk()
        ->assertSee('RM 50.00')
        ->assertDontSee('RM 500.00');
});

it('filters donations by type and date range', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);

    Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subDays(2),
    ]);
    Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'type' => DonationType::OneTime,
        'status' => DonationStatus::Succeeded,
        'created_at' => now()->subDays(10),
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', [
            'organization' => $org,
            'type' => DonationType::Recurring->value,
            'date_from' => now()->subDays(5)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]))
        ->assertOk()
        ->assertSee('Recurring')
        ->assertDontSee('One-time');
});

it('filters donations page by subscription id', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create([
        'organization_id' => $org->getKey(),
        'title' => 'Subscription Filter Campaign',
    ]);
    $otherCampaign = Campaign::factory()->create([
        'organization_id' => $org->getKey(),
        'title' => 'Unfiltered Campaign',
    ]);

    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
    ]);
    $relatedDonation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'subscription_id' => $subscription->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);
    $otherDonation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $otherCampaign->getKey(),
        'subscription_id' => null,
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', ['organization' => $org, 'subscription' => $subscription->public_id]))
        ->assertOk()
        ->assertSee('Payment history for')
        ->assertSee($relatedDonation->campaign->title)
        ->assertDontSee($otherDonation->campaign->title);
});

it('does not filter donations by another organizations subscription id', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create([
        'organization_id' => $org->getKey(),
        'title' => 'Visible Org Donation',
    ]);
    $otherCampaign = Campaign::factory()->create([
        'organization_id' => $otherOrg->getKey(),
        'title' => 'Hidden Subscription Donation',
    ]);

    $otherSubscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $otherCampaign->getKey(),
    ]);
    $orgDonation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'subscription_id' => null,
        'status' => DonationStatus::Succeeded,
    ]);
    $otherDonation = Donation::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $otherCampaign->getKey(),
        'subscription_id' => $otherSubscription->getKey(),
        'status' => DonationStatus::Succeeded,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', ['organization' => $org, 'subscription' => $otherSubscription->public_id]))
        ->assertOk()
        ->assertSee($orgDonation->campaign->title)
        ->assertDontSee('Payment history for')
        ->assertDontSee($otherDonation->campaign->title);
});

it('shows history button for subscriptions in donor portal', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.subscriptions', $org))
        ->assertSee('History')
        ->assertSee(route('donorportal.donations', ['organization' => $org, 'subscription' => $subscription->public_id]), false);
});

it('renders profile page with view and edit mode', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.profile', $org))
        ->assertOk()
        ->assertSee('Profile')
        ->assertSee($donor->name)
        ->assertSee($donor->email);
});

it('updates donor profile name', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.profile.update', $org), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $donor->email,
        ])
        ->assertRedirect(route('donorportal.profile', $org));

    $this->assertEquals('Updated Name', $donor->fresh()->name);
});

it('updates donor profile with photo', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $photo = UploadedFile::fake()->image('profile.jpg', 100, 100);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.profile.update', $org), [
            'first_name' => 'Photo',
            'last_name' => 'Donor',
            'email' => $donor->email,
            'photo' => $photo,
        ])
        ->assertRedirect(route('donorportal.profile', $org));

    expect($donor->fresh()->photo_path)->not()->toBeNull();
});

it('submits report problem and redirects to dashboard', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    User::factory()->create([
        'organization_id' => $org->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.report-problem', $org), [
            'message' => 'Test problem report',
        ])
        ->assertRedirect(route('donorportal.dashboard', $org));
});

it('rejects empty message in report problem', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.report-problem', $org), [
            'message' => '',
        ])
        ->assertSessionHasErrors('message');
});

it('subscribes cancel requires authorization and returns redirect', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
        'stripe_subscription_id' => 'sub_test_cancel',
    ]);

    $this->mock(ManageStripeSubscription::class)
        ->shouldReceive('cancel')
        ->once()
        ->with(Mockery::on(fn ($s) => $s->getKey() === $subscription->getKey()), false)
        ->andReturnNull();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.subscriptions.cancel', ['organization' => $org, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $org))
        ->assertSessionHas('success');
});

it('denies cancel subscription from another donor', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $otherDonor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $otherDonor->getKey(),
        'campaign_id' => $campaign->getKey(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.subscriptions.cancel', ['organization' => $org, 'subscription' => $subscription]))
        ->assertForbidden();
});

it('denies cancel subscription from another organization', function () {
    $org = Organization::factory()->create();
    $otherOrg = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $otherOrg->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.subscriptions.cancel', ['organization' => $org, 'subscription' => $subscription]))
        ->assertForbidden();
});

it('subscribes pause requires authorization and returns redirect', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
    ]);

    $this->mock(ManageStripeSubscription::class)
        ->shouldReceive('pause')
        ->once()
        ->andReturnNull();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.subscriptions.pause', ['organization' => $org, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $org))
        ->assertSessionHas('success');
});

it('subscribes resume requires authorization and returns redirect', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
    ]);

    $this->mock(ManageStripeSubscription::class)
        ->shouldReceive('resume')
        ->once()
        ->andReturnNull();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.subscriptions.resume', ['organization' => $org, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $org))
        ->assertSessionHas('success');
});

it('subscribes change amount validates and returns redirect', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
        'amount' => 50.00,
    ]);

    $this->mock(ManageStripeSubscription::class)
        ->shouldReceive('changeAmount')
        ->once()
        ->with(Mockery::on(fn ($s) => $s->getKey() === $subscription->getKey()), 100.0)
        ->andReturnNull();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.subscriptions.change-amount', ['organization' => $org, 'subscription' => $subscription]), [
            'new_amount' => 100,
        ])
        ->assertRedirect(route('donorportal.subscriptions', $org))
        ->assertSessionHas('success');
});

it('rejects invalid amount in change subscription', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.subscriptions.change-amount', ['organization' => $org, 'subscription' => $subscription]), [
            'new_amount' => 0,
        ])
        ->assertSessionHasErrors('new_amount');
});

it('validates required fields on profile update', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.profile.update', $org), [
            'first_name' => '',
            'email' => 'not-an-email',
        ])
        ->assertSessionHasErrors(['first_name', 'email']);
});

it('handles subscription action error gracefully', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
    ]);

    $this->mock(ManageStripeSubscription::class)
        ->shouldReceive('cancel')
        ->once()
        ->andThrow(new Exception('Stripe error'));

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.subscriptions.cancel', ['organization' => $org, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.subscriptions', $org))
        ->assertSessionHas('error');
});

it('renders subscription increase page for authenticated donor', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
        'amount' => 108.00,
        'currency' => 'myr',
        'interval' => 'monthly',
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.subscriptions.increase', ['organization' => $org, 'subscription' => $subscription]))
        ->assertOk()
        ->assertSee('Boost your impact by increasing your donations')
        ->assertSee('RM 108.00 MYR/mo')
        ->assertSee('+ RM5')
        ->assertSee('+ RM80')
        ->assertSee('+ RM100')
        ->assertSee('Other amount')
        ->assertSee('Confirm')
        ->assertSee('No, thanks');
});

it('requires authentication for subscription increase page', function () {
    $org = Organization::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'campaign_id' => $campaign->getKey(),
    ]);

    $this->get(route('donorportal.subscriptions.increase', ['organization' => $org, 'subscription' => $subscription]))
        ->assertRedirect(route('donorportal.login', $org));
});

it('denies subscription increase page for another donor subscription', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $otherDonor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $otherDonor->getKey(),
        'campaign_id' => $campaign->getKey(),
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.subscriptions.increase', ['organization' => $org, 'subscription' => $subscription]))
        ->assertForbidden();
});

it('submits subscription increase from dedicated page', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
        'amount' => 108.00,
    ]);

    $this->mock(ManageStripeSubscription::class)
        ->shouldReceive('changeAmount')
        ->once()
        ->with(Mockery::on(fn ($s) => $s->getKey() === $subscription->getKey()), 188.0)
        ->andReturnNull();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->postJson(route('donorportal.subscriptions.change-amount', ['organization' => $org, 'subscription' => $subscription]), [
            'new_amount' => 188,
        ])
        ->assertOk()
        ->assertJson([
            'success' => true,
            'new_amount' => 188.0,
        ]);
});

it('sends amount change notification to donor and org admins', function () {
    Mail::fake();

    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $admin = User::factory()->create([
        'organization_id' => $org->getKey(),
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
        'amount' => 50.00,
    ]);

    $this->mock(ManageStripeSubscription::class)
        ->shouldReceive('changeAmount')
        ->once()
        ->andReturnNull();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->postJson(route('donorportal.subscriptions.change-amount', ['organization' => $org, 'subscription' => $subscription]), [
            'new_amount' => 100,
        ])
        ->assertOk();

    Mail::assertQueued(SupporterSubscriptionAmountChangedNotification::class, function ($mail) use ($donor) {
        return $mail->hasTo($donor->email);
    });
    Mail::assertQueued(SubscriptionAmountChangedNotification::class, function ($mail) use ($admin) {
        return $mail->hasTo($admin->email);
    });

    $adminMail = Mail::queued(SubscriptionAmountChangedNotification::class)->first();
    $html = $adminMail->render();

    expect($html)
        ->toContain('Hi '.$admin->name)
        ->toContain('Recurring plan has been changed')
        ->toContain($subscription->public_id)
        ->toContain($campaign->title)
        ->toContain($donor->name)
        ->toContain($donor->email)
        ->toContain('RM 50.00')
        ->toContain('Total amount');
});

it('shows change amount button in subscriptions list instead of standalone page link', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();
    $campaign = Campaign::factory()->create(['organization_id' => $org->getKey()]);
    $subscription = Subscription::factory()->create([
        'donor_id' => $donor->getKey(),
        'campaign_id' => $campaign->getKey(),
        'status' => SubscriptionStatus::Active,
        'amount' => 50.00,
        'currency' => 'myr',
        'interval' => 'monthly',
    ]);

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.subscriptions', $org))
        ->assertOk()
        ->assertSee('Change Amount')
        ->assertDontSee(route('donorportal.subscriptions.increase', [$org, $subscription]), false);
});

it('redirects to a local path after magic login but blocks open redirects', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create([
        'magic_token' => hash('sha256', 'valid-token-123'),
        'magic_token_expires_at' => now()->addHours(24),
    ]);

    $this->get(route('donorportal.magic-login', [
        'organization' => $org,
        'token' => 'valid-token-123',
        'redirect' => '/donorportal/dashboard',
    ]))
        ->assertRedirect('/donorportal/dashboard');

    $donor->fresh();

    $this->get(route('donorportal.magic-login', [
        'organization' => $org,
        'token' => 'valid-token-123',
        'redirect' => '//evil.com',
    ]))
        ->assertRedirect(route('donorportal.dashboard', $org));
});

it('validates donation list filters', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->get(route('donorportal.donations', [
            'organization' => $org,
            'date_from' => 'not-a-date',
            'amount_min' => 'abc',
        ]))
        ->assertSessionHasErrors(['date_from', 'amount_min']);
});

it('throttles report problem submissions', function () {
    $org = Organization::factory()->create();
    $donor = Donor::factory()->create();

    for ($i = 0; $i < 5; $i++) {
        $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
            ->post(route('donorportal.report-problem', $org), [
                'message' => 'Problem '.$i,
            ])
            ->assertRedirect(route('donorportal.dashboard', $org));
    }

    $this->withSession(['donor_id' => $donor->getKey(), 'organization_id' => $org->getKey()])
        ->post(route('donorportal.report-problem', $org), [
            'message' => 'Problem 6',
        ])
        ->assertRedirect(route('donorportal.dashboard', $org))
        ->assertSessionHas('error');
});
