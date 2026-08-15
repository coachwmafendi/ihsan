<?php

declare(strict_types=1);

use App\Actions\Stripe\SyncDonorDetailsToStripe;
use App\Enums\DonationStatus;
use App\Enums\UserRole;
use App\Livewire\App\Supporters\SupporterShow;
use App\Mail\DonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

it('renders the supporter detail page with sections and menus', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee($donor->name)
        ->assertSee('Information')
        ->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Language')
        ->assertSee('Mailing Address')
        ->assertSee('Donations')
        ->assertSee('Receipts')
        ->assertSee('Make donation')
        ->assertSee('Open Donor Portal')
        ->assertSee('Information');
});

it('renders donation date with malaysian time and payment method icon', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create([
        'created_at' => now()->timezone('Asia/Kuala_Lumpur')->setTime(14, 30),
        'payment_method_brand' => 'visa',
        'payment_method_type' => 'card',
    ]);

    $expectedTime = myrTime($donation->created_at);

    expect($donation->card_icon_component)->toBe('icons.visa');

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee($expectedTime);
});

it('shows installment number badge for recurring donations', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($donor)->for($campaign)->create();

    $firstDonation = Donation::factory()->for($donor)->for($campaign)->create([
        'subscription_id' => $subscription->id,
        'created_at' => now()->subDays(2),
    ]);
    $secondDonation = Donation::factory()->for($donor)->for($campaign)->create([
        'subscription_id' => $subscription->id,
        'created_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSeeHtml('1')
        ->assertSeeHtml('2');
});

it('shows approximate myr lifetime total when foreign donations lack base amount', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($donor)->for($campaign)->create([
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => null,
    ]);
    Donation::factory()->for($donor)->for($campaign)->create([
        'currency' => 'usd',
        'gross_amount' => 100.00,
        'base_amount' => null,
    ]);

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Lifetime donated ≈ MYR 200.00');
});

it('hides recurring plans section and menu when supporter has no subscriptions', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertDontSeeHtml('id="recurring-plans"')
        ->assertDontSeeHtml('href="#recurring-plans"');
});

it('shows recurring plans section and menu when supporter has subscriptions', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();
    Subscription::factory()->for($donor)->for($campaign)->create();

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSeeHtml('id="recurring-plans"')
        ->assertSeeHtml('href="#recurring-plans"');
});

it('shows a validated badge next to the email once a delivery is confirmed', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();
    DonorEmailLog::factory()->for($donor)->delivered()->create();

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Validated');
});

it('hides the validated badge when no delivery has been confirmed', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();
    DonorEmailLog::factory()->for($donor)->create();

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertDontSee('Validated');
});

it('hides the validated badge when the email has bounced', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'email_bounced_at' => now(),
    ]);
    Donation::factory()->for($donor)->for($campaign)->create();
    DonorEmailLog::factory()->for($donor)->delivered()->create();

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertDontSee('Validated');
});

it('marks the active section menu with intersection observer data', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $component = Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor]);

    $component->assertSeeHtml('id="information"')
        ->assertSeeHtml('id="donations"')
        ->assertSeeHtml('id="receipts"')
        ->assertSeeHtml('href="#information"')
        ->assertSeeHtml('href="#donations"')
        ->assertSeeHtml('href="#receipts"')
        ->assertSeeHtml('sticky top-24');
});

it('marks the active section menu including recurring plans when subscriptions exist', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();
    Subscription::factory()->for($donor)->for($campaign)->create();

    $component = Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor]);

    $component->assertSeeHtml('id="information"')
        ->assertSeeHtml('id="donations"')
        ->assertSeeHtml('id="recurring-plans"')
        ->assertSeeHtml('id="receipts"')
        ->assertSeeHtml('href="#information"')
        ->assertSeeHtml('href="#donations"')
        ->assertSeeHtml('href="#recurring-plans"')
        ->assertSeeHtml('href="#receipts"')
        ->assertSeeHtml('sticky top-24');
});

it('renders an impersonation form on the open donor portal action', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Open Donor Portal')
        ->assertSeeHtml('action="'.e(route('admin.donor-portal.impersonate', $donor)).'"')
        ->assertSeeHtml('target="_blank"');
});

it('shows validation errors when saving an invalid email address', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['email' => 'ali@example.com']);
    Donation::factory()->for($donor)->for($campaign)->create();

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->call('openEditModal')
        ->set('email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['email']);

    expect($donor->fresh()->email)->toBe('ali@example.com');
});

it('shows validation error when saving a duplicate email address', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();

    Donor::factory()->create(['email' => 'existing@example.com']);

    $donor = Donor::factory()->create(['email' => 'ali@example.com']);
    Donation::factory()->for($donor)->for($campaign)->create();

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->call('openEditModal')
        ->set('email', 'existing@example.com')
        ->call('save')
        ->assertHasErrors(['email']);

    expect($donor->fresh()->email)->toBe('ali@example.com');
});

it('keeps the validated badge after the supporter email is updated', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();

    $donor = Donor::factory()->create(['email' => 'ali@example.com']);
    Donation::factory()->for($donor)->for($campaign)->create();
    DonorEmailLog::factory()->for($donor)->delivered()->create();

    expect($donor->hasValidatedEmail())->toBeTrue();

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->call('openEditModal')
        ->set('firstName', 'Ali')
        ->set('lastName', 'Abu')
        ->set('email', 'new.email@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $donor = $donor->fresh();

    expect($donor)
        ->email->toBe('new.email@example.com')
        ->hasValidatedEmail()->toBeTrue();

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Validated');
});

it('opens the edit modal and saves the supporter details', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['first_name' => 'Ali', 'last_name' => 'Abu', 'email' => 'ali@example.com']);
    Donation::factory()->for($donor)->for($campaign)->create();

    $component = Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor]);

    $component->assertSeeHtml('wire:click="openEditModal"')
        ->call('openEditModal')
        ->assertSet('editing', true)
        ->assertSet('firstName', 'Ali')
        ->assertSet('lastName', 'Abu')
        ->assertSet('email', 'ali@example.com');

    $component->set('firstName', 'Siti')
        ->set('lastName', 'Aminah')
        ->set('email', 'siti@example.com')
        ->call('save');

    expect($donor->fresh())
        ->name->toBe('Siti Aminah')
        ->email->toBe('siti@example.com');

    $component->assertSet('editing', false);
});

it('syncs supporter details to stripe when stripe_customer_id exists', function () {
    $organization = Organization::factory()->stripeConnected()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create([
        'stripe_customer_id' => 'cus_test_123',
        'first_name' => 'Ali',
        'last_name' => 'Abu',
        'email' => 'ali@example.com',
    ]);
    Donation::factory()->for($donor)->for($campaign)->create();

    $spy = Mockery::mock(new SyncDonorDetailsToStripe);
    $spy->shouldReceive('sync')
        ->once()
        ->withArgs(fn (Donor $d, Organization $o) => $d->is($donor) && $o->is($organization))
        ->andReturn(true);
    app()->instance(SyncDonorDetailsToStripe::class, $spy);

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->call('openEditModal')
        ->set('firstName', 'Siti')
        ->set('lastName', 'Aminah')
        ->set('email', 'siti@example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect($donor->fresh())
        ->name->toBe('Siti Aminah')
        ->email->toBe('siti@example.com');
});

it('renders the emails section with sent emails for the donor', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    DonorEmailLog::factory()->donation($donation)->create([
        'subject' => 'Your Donation Receipt — '.$organization->name,
        'sent_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Emails')
        ->assertSee('Sent')
        ->assertSee('Subject')
        ->assertSee('Opened')
        ->assertSee('Resend')
        ->assertSee('Your Donation Receipt — '.$organization->name);
});

it('shows empty state when no emails have been sent to the donor', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    Donation::factory()->for($donor)->for($campaign)->create();

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Emails')
        ->assertSee('No emails yet');
});

it('does not show email logs from other organizations', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $otherOrganization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    DonorEmailLog::factory()->donation($donation)->create([
        'organization_id' => $otherOrganization->getKey(),
        'subject' => 'Other Org Email',
        'sent_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Emails')
        ->assertDontSee('Other Org Email')
        ->assertSee('No emails yet');
});

it('resends a donation receipt email and creates a new log entry', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $log = DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$organization->name,
        'sent_at' => now()->subDay(),
    ]);

    $component = Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor]);

    $component->call('confirmResend', $log->getKey())
        ->assertSet('showResendModal', true)
        ->assertSet('resendLogId', $log->getKey())
        ->assertSet('resendRecipientEmail', $donor->email);

    $component->call('resendConfirmed')
        ->assertSet('showResendModal', false)
        ->assertDispatched('notify', variant: 'success');

    Mail::assertQueued(DonationReceipt::class, function (DonationReceipt $mail) use ($donation) {
        return $mail->donation->is($donation);
    });

    expect($log->fresh()->resends)->toHaveCount(1);
});

it('closes both modals after resending from the preview modal', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $log = DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$organization->name,
        'sent_at' => now()->subDay(),
    ]);

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->call('previewEmail', $log->getKey())
        ->assertSet('showPreviewModal', true)
        ->call('resendFromModal')
        ->assertSet('showPreviewModal', true)
        ->assertSet('showResendModal', true)
        ->call('resendConfirmed')
        ->assertSet('showResendModal', false)
        ->assertSet('showPreviewModal', false);
});

it('resends a donation receipt to an edited email address', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['email' => 'original@example.com']);
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $log = DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$organization->name,
        'sent_at' => now()->subDay(),
    ]);

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->call('confirmResend', $log->getKey())
        ->set('resendRecipientEmail', 'edited@example.com')
        ->call('resendConfirmed')
        ->assertDispatched('notify', variant: 'success');

    Mail::assertQueued(DonationReceipt::class, function (DonationReceipt $mail) {
        return $mail->hasTo('edited@example.com');
    });
});

it('opens a preview modal with rendered email html when subject is clicked', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $log = DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$organization->name,
    ]);

    $component = Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor]);

    $component->call('previewEmail', $log->getKey())
        ->assertSet('showPreviewModal', true)
        ->assertSet('previewSubject', $log->subject)
        ->assertSet('previewHtml', fn ($html) => is_string($html) && str_contains($html, 'Thank you for your donation!'));
});

it('notifies the user when an email cannot be previewed', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $log = DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => 'App\\Mail\\UnknownMailable',
        'subject' => 'Unknown email',
    ]);

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->call('previewEmail', $log->getKey())
        ->assertSet('showPreviewModal', false)
        ->assertDispatched('notify', variant: 'danger');
});

it('shows the system from address in the email preview modal', function () {
    config()->set('mail.from.address', 'no-reply@getihsan.my');

    $organization = Organization::factory()->create([
        'name' => 'Test Org',
        'contact_email' => 'testorg@gmail.com',
    ]);
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $log = DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — Test Org',
    ]);

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->call('previewEmail', $log->getKey())
        ->assertSet('previewFromName', 'Test Org')
        ->assertSet('previewFromEmail', 'no-reply@getihsan.my');
});

it('shows the custom recipient email for a resent log entry', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['email' => 'original@example.com']);
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $log = DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$organization->name,
        'metadata' => ['resent_to_email' => 'resent@example.com'],
    ]);

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->call('previewEmail', $log->getKey())
        ->assertSet('previewToEmail', 'resent@example.com');
});

it('shows a resent badge next to the subject for resend log entries', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    $originalLog = DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$organization->name,
    ]);

    $resentLog = DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$organization->name,
        'resent_from_id' => $originalLog->getKey(),
    ]);

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->assertSee($resentLog->subject)
        ->assertSee('Resent');
});

it('does not show a resent badge for original email log entries', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'subject' => 'Your Donation Receipt — '.$organization->name,
        'resent_from_id' => null,
    ]);

    Livewire::actingAs($user)
        ->test(SupporterShow::class, ['donor' => $donor])
        ->assertSee($donation->invoice_number)
        ->assertDontSee('Resent');
});

it('shows a receipt download link for succeeded donations', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create([
        'status' => DonationStatus::Succeeded,
        'invoice_number' => 'INV-SUC-001',
    ]);

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSeeHtml('href="'.e(route('donations.receipt.download', ['donation' => $donation->public_id])).'"')
        ->assertSee('INV-SUC-001');
});

it('does not show non-succeeded donations in the receipts section', function (DonationStatus $status) {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create([
        'status' => $status,
        'invoice_number' => 'INV-'.$status->value.'-001',
    ]);

    $this->actingAs($user)
        ->get('https://app.example.test/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertDontSeeHtml('href="'.e(route('donations.receipt.download', ['donation' => $donation->public_id])).'"')
        ->assertDontSee('INV-'.$status->value.'-001');
})->with([
    DonationStatus::Pending,
    DonationStatus::Failed,
    DonationStatus::Refunded,
    DonationStatus::Cancelled,
]);
