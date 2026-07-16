<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\SendDonorRecurringPaymentNotification;
use App\Mail\DonorRecurringPaymentNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('sends a thank you email to a supporter for subsequent recurring payments', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['locale' => 'en']);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'subscription_id' => $subscription->getKey(),
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
    ]);

    (new SendDonorRecurringPaymentNotification($donation))->handle();

    Mail::assertQueued(DonorRecurringPaymentNotification::class, function (DonorRecurringPaymentNotification $mail) use ($donation, $organization) {
        $html = $mail->render();

        return $mail->donation->is($donation)
            && str_contains($html, 'We sincerely appreciate your continued support.')
            && str_contains($html, 'On behalf of everyone at '.$organization->name)
            && str_contains($html, 'Your friends at '.$organization->name)
            && str_contains($html, 'Download Receipt')
            && str_contains($html, route('donorportal.dashboard', $organization))
            && str_contains($html, 'Don’t send me these emails anymore');
    });

    $log = DonorEmailLog::query()
        ->where('donation_id', $donation->getKey())
        ->where('mailable_class', DonorRecurringPaymentNotification::class)
        ->first();

    expect($log)
        ->not->toBeNull()
        ->subject->toBe('Thank you for your continued support!')
        ->donor_id->toBe($donor->getKey())
        ->organization_id->toBe($organization->getKey())
        ->subscription_id->toBe($subscription->getKey());
});

it('does not send duplicate recurring payment emails for the same donation', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'subscription_id' => $subscription->getKey(),
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
    ]);

    (new SendDonorRecurringPaymentNotification($donation))->handle();
    (new SendDonorRecurringPaymentNotification($donation))->handle();

    Mail::assertQueued(DonorRecurringPaymentNotification::class, 1);

    expect(DonorEmailLog::query()
        ->where('donation_id', $donation->getKey())
        ->where('mailable_class', DonorRecurringPaymentNotification::class)
        ->count())->toBe(1);
});

it('does not send the recurring payment email when the supporter has opted out', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['email_opt_out_at' => now()]);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'subscription_id' => $subscription->getKey(),
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
    ]);

    (new SendDonorRecurringPaymentNotification($donation))->handle();

    Mail::assertNothingQueued();
});

it('does not send the recurring payment email for one-time donations', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'type' => DonationType::OneTime,
        'status' => DonationStatus::Succeeded,
    ]);

    (new SendDonorRecurringPaymentNotification($donation))->handle();

    Mail::assertNothingQueued();
});

it('renders the recurring payment email in malay when the supporter locale is ms', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['locale' => 'ms']);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'subscription_id' => $subscription->getKey(),
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
    ]);

    $mailable = new DonorRecurringPaymentNotification($donation);

    expect($mailable->render())->toContain('Kami amat menghargai sokongan berterusan anda.')
        ->toContain('+ MYR 15/bln')
        ->toContain('+ MYR 25/bln')
        ->toContain('+ MYR 35/bln')
        ->and($mailable->envelope()->subject)->toBe('Terima kasih atas sokongan berterusan anda!');
});

it('renders upgrade chips for recurring donations with signed donor portal links', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['locale' => 'en']);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create([
        'amount' => 38.10,
        'currency' => 'usd',
    ]);
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'subscription_id' => $subscription->getKey(),
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
    ]);

    $mailable = new DonorRecurringPaymentNotification($donation);
    $html = $mailable->render();

    expect($html)
        ->toContain('Modify your USD 38.10 monthly donation')
        ->toContain('+ USD 15/mo')
        ->toContain('+ USD 25/mo')
        ->toContain('+ USD 35/mo')
        ->toContain(route('donorportal.subscriptions.increase-link', [$organization->code, $subscription->public_id]))
        ->toContain('increments=')
        ->toContain('selected=15')
        ->toContain('selected=25')
        ->toContain('selected=35')
        ->toContain('signature=')
        ->toContain('expires=');
});
