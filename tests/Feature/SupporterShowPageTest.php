<?php

declare(strict_types=1);

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
        ->get('/app/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee($donor->name)
        ->assertSee('Information')
        ->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Language')
        ->assertSee('Mailing address')
        ->assertSee('Donations')
        ->assertSee('Receipts')
        ->assertSee('Make donation')
        ->assertSee('Open Donor Portal')
        ->assertSee('Information');
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
        ->get('/app/supporters/'.$donor->public_id)
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
        ->get('/app/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertDontSee('Recurring plans')
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
        ->get('/app/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Recurring plans')
        ->assertSeeHtml('id="recurring-plans"')
        ->assertSeeHtml('href="#recurring-plans"');
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
        ->get('/app/supporters/'.$donor->public_id)
        ->assertOk()
        ->assertSee('Open Donor Portal')
        ->assertSeeHtml('action="'.e(route('admin.donor-portal.impersonate', $donor)).'"')
        ->assertSeeHtml('target="_blank"');
});

it('opens the edit modal and saves the supporter details', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->for($organization)->create([
        'role' => UserRole::NgoAdmin,
    ]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['name' => 'Ali Abu', 'email' => 'ali@example.com']);
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
        ->set('updateRecurringPlans', false)
        ->call('save');

    expect($donor->fresh())
        ->name->toBe('Siti Aminah')
        ->email->toBe('siti@example.com');

    $component->assertSet('editing', false);
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
        ->get('/app/supporters/'.$donor->public_id)
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
        ->get('/app/supporters/'.$donor->public_id)
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
        ->get('/app/supporters/'.$donor->public_id)
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
        ->assertSet('showPreviewModal', false)
        ->assertDispatched('notify', variant: 'success');

    Mail::assertQueued(DonationReceipt::class, function (DonationReceipt $mail) use ($donation) {
        return $mail->donation->is($donation);
    });

    expect($log->fresh()->resends)->toHaveCount(1);
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
