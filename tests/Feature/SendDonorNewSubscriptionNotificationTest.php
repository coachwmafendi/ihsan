<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\SendDonorNewSubscriptionNotification;
use App\Mail\DonorNewSubscriptionNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\Subscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

it('sends a welcome email to a supporter when a recurring subscription starts', function () {
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

    (new SendDonorNewSubscriptionNotification($donation))->handle();

    Mail::assertQueued(DonorNewSubscriptionNotification::class, function (DonorNewSubscriptionNotification $mail) use ($donation, $organization) {
        $html = $mail->render();

        return $mail->donation->is($donation)
            && str_contains($html, 'Thank you for your generous donation and for choosing to support us on a recurring basis!')
            // Escaped as the template renders it: a factory name containing an
            // ampersand or an apostrophe comes back as &amp; or &#039;, and the
            // raw comparison failed only for those names.
            && str_contains($html, 'On behalf of everyone at '.e($organization->name))
            && str_contains($html, 'Your friends at '.e($organization->name))
            && str_contains($html, 'Download Receipt')
            && str_contains($html, route('donorportal.dashboard', $organization))
            && str_contains($html, 'Don’t send me these emails anymore');
    });

    $log = DonorEmailLog::query()
        ->where('donation_id', $donation->getKey())
        ->where('mailable_class', DonorNewSubscriptionNotification::class)
        ->first();

    expect($log)
        ->not->toBeNull()
        ->subject->toBe('Thank you for joining as a recurring supporter')
        ->donor_id->toBe($donor->getKey())
        ->organization_id->toBe($organization->getKey())
        ->subscription_id->toBe($subscription->getKey());
});

it('does not send duplicate welcome emails for the same donation', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'subscription_id' => $subscription->getKey(),
    ]);

    (new SendDonorNewSubscriptionNotification($donation))->handle();
    (new SendDonorNewSubscriptionNotification($donation))->handle();

    Mail::assertQueued(DonorNewSubscriptionNotification::class, 1);

    expect(DonorEmailLog::query()
        ->where('donation_id', $donation->getKey())
        ->where('mailable_class', DonorNewSubscriptionNotification::class)
        ->count())->toBe(1);
});

it('does not send the welcome email when the supporter has opted out', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['email_opt_out_at' => now()]);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'subscription_id' => $subscription->getKey(),
    ]);

    (new SendDonorNewSubscriptionNotification($donation))->handle();

    Mail::assertNothingQueued();
});

it('renders the welcome email in malay when the supporter locale is ms', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['locale' => 'ms']);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'subscription_id' => $subscription->getKey(),
    ]);

    $mailable = new DonorNewSubscriptionNotification($donation);

    expect($mailable->render())->toContain('Terima kasih kerana menyertai sebagai penyokong berulang')
        ->and($mailable->envelope()->subject)->toBe('Terima kasih kerana menyertai sebagai penyokong berulang');
});

it('greets an organisation whose name has to be escaped', function () {
    // The check compared the raw name against rendered HTML, so it failed only
    // when the factory happened to generate a name with an ampersand or an
    // apostrophe - a test that fails at random teaches people to ignore it.
    Mail::fake();

    $organization = Organization::factory()->create(['name' => "Hassan & Sons' Trust"]);
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['locale' => 'en']);
    $subscription = Subscription::factory()->for($campaign)->for($donor)->create();
    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'subscription_id' => $subscription->getKey(),
        'type' => DonationType::Recurring,
        'status' => DonationStatus::Succeeded,
    ]);

    (new SendDonorNewSubscriptionNotification($donation))->handle();

    Mail::assertQueued(DonorNewSubscriptionNotification::class, function (DonorNewSubscriptionNotification $mail) use ($organization) {
        return str_contains($mail->render(), 'On behalf of everyone at '.e($organization->name));
    });
});
