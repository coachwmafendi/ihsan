<?php

use App\Actions\DonorEmailLog\PreviewDonorEmail;
use App\Actions\DonorEmailLog\ResendDonorEmail;
use App\Jobs\SendDonorRefundNotification;
use App\Mail\DonorRefundNotification;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use Illuminate\Support\Facades\Mail;

test('donor refund email renders refund details', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'Ahmad Ismail']);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 100.00,
    ]);

    $mailable = new DonorRefundNotification($donation, 'MYR 100.00');

    expect($mailable->envelope()->subject)
        ->toBe('Your donation has been refunded — Test Org');

    $html = $mailable->render();

    expect($html)
        ->toContain('Donation Refunded')
        ->toContain('Hi Ahmad Ismail')
        ->toContain('MYR 100.00')
        ->toContain('Test Campaign')
        ->toContain('Donation ID')
        ->toContain($donation->public_id)
        ->toContain('donor/notifications/'.$donor->public_id)
        ->toContain('Amount Refunded')
        ->toContain('Campaign');
});

test('job sends donor refund email once and logs it', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 50.00,
    ]);

    $job = new SendDonorRefundNotification($donation);
    $job->handle();
    $job->handle();

    Mail::assertQueued(DonorRefundNotification::class, 1);

    expect(DonorEmailLog::query()
        ->where('donation_id', $donation->getKey())
        ->where('mailable_class', DonorRefundNotification::class)
        ->count())
        ->toBe(1);
});

test('job does not send email when donor has opted out', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create(['email_opt_out_at' => now()]);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 50.00,
    ]);

    $job = new SendDonorRefundNotification($donation);
    $job->handle();

    Mail::assertNothingQueued();
});

test('logged donor refund email can be previewed', function () {
    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'Siti Aminah']);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 75.00,
    ]);

    (new SendDonorRefundNotification($donation))->handle();

    $log = $donor->fresh()->emailLogs()->first();

    $html = app(PreviewDonorEmail::class)->handle($log);

    expect($html)
        ->not->toBeNull()
        ->toContain('Donation Refunded')
        ->toContain('MYR 75.00')
        ->toContain('Test Campaign')
        ->toContain('Hi Siti Aminah');
});

test('logged donor refund email can be resent', function () {
    Mail::fake();

    $organization = Organization::factory()->create(['name' => 'Test Org']);
    $campaign = Campaign::factory()->for($organization)->create(['title' => 'Test Campaign']);
    $donor = Donor::factory()->create(['name' => 'Siti Aminah']);

    $donation = Donation::factory()->for($campaign)->for($donor)->create([
        'currency' => 'myr',
        'gross_amount' => 75.00,
    ]);

    (new SendDonorRefundNotification($donation))->handle();

    $originalLog = $donor->fresh()->emailLogs()->first();

    $newLog = app(ResendDonorEmail::class)->handle($originalLog);

    expect($newLog)
        ->not->toBeNull()
        ->resent_from_id->toBe($originalLog->getKey())
        ->mailable_class->toBe(DonorRefundNotification::class);

    expect($originalLog->fresh()->resends)->toHaveCount(1);

    Mail::assertQueued(DonorRefundNotification::class, function (DonorRefundNotification $mail) use ($donation) {
        return $mail->donation->is($donation);
    });
});
