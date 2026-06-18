<?php

declare(strict_types=1);

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Actions\DonorEmailLog\ResendDonorEmail;
use App\Console\Commands\BackfillDonorEmailLogs;
use App\Jobs\SendDonationReceipt;
use App\Mail\DonationReceipt;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;

it('logs a donor email when a donation receipt is sent', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    expect($donor->emailLogs()->count())->toBe(0);

    $job = new SendDonationReceipt($donation);
    $job->handle();

    expect($donor->fresh()->emailLogs()->count())->toBe(1);

    $log = $donor->emailLogs()->first();
    expect($log)
        ->mailable_class->toBe(DonationReceipt::class)
        ->subject->toBe('Your Donation Receipt — '.config('app.name'))
        ->organization_id->toBe($organization->getKey())
        ->donation_id->toBe($donation->getKey());
});

it('resends a logged donor email and links it to the original log', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $donation = Donation::factory()->for($donor)->for($campaign)->create();

    SendDonationReceipt::dispatchSync($donation);

    $originalLog = $donor->fresh()->emailLogs()->first();

    $newLog = app(ResendDonorEmail::class)->handle($originalLog);

    expect($newLog)
        ->not->toBeNull()
        ->resent_from_id->toBe($originalLog->getKey())
        ->mailable_class->toBe(DonationReceipt::class);

    expect($originalLog->fresh()->resends)->toHaveCount(1);

    Mail::assertQueued(DonationReceipt::class, function (DonationReceipt $mail) use ($donation) {
        return $mail->donation->is($donation);
    });
});

it('records no subject when mailable envelope throws', function () {
    $organization = Organization::factory()->create();
    $donor = Donor::factory()->create();

    $mailable = new class extends Mailable
    {
        public function envelope(): Envelope
        {
            throw new RuntimeException('Envelope failure');
        }

        public function content(): Content
        {
            return new Content(view: 'emails.donation-receipt');
        }
    };

    $log = app(LogDonorEmail::class)->handle(
        donor: $donor,
        mailable: $mailable,
        organization: $organization,
    );

    expect($log->subject)->toBe('No subject');
});

it('backfills email logs from historical donation receipts', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();
    $sentAt = now()->subMonth();

    $donation = Donation::factory()->for($donor)->for($campaign)->create([
        'receipt_sent_at' => $sentAt,
    ]);

    $this->artisan(BackfillDonorEmailLogs::class, ['--force' => true])
        ->expectsOutputToContain('Backfilling 1 donation receipt email log')
        ->assertSuccessful();

    $log = DonorEmailLog::query()->where('donation_id', $donation->getKey())->first();

    expect($log)
        ->not->toBeNull()
        ->mailable_class->toBe(DonationReceipt::class)
        ->donor_id->toBe($donor->getKey())
        ->organization_id->toBe($organization->getKey());

    expect($log->sent_at->toDateTimeString())->toBe($sentAt->toDateTimeString());
});

it('skips donations that already have a donation receipt log', function () {
    Mail::fake();

    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    $donation = Donation::factory()->for($donor)->for($campaign)->create([
        'receipt_sent_at' => now()->subMonth(),
    ]);

    DonorEmailLog::factory()->donation($donation)->create([
        'mailable_class' => DonationReceipt::class,
        'sent_at' => now()->subWeek(),
    ]);

    $this->artisan(BackfillDonorEmailLogs::class, ['--force' => true])
        ->expectsOutputToContain('No donations need backfilling.')
        ->assertSuccessful();
});

it('does not backfill without force flag', function () {
    $organization = Organization::factory()->create();
    $campaign = Campaign::factory()->for($organization)->create();
    $donor = Donor::factory()->create();

    Donation::factory()->for($donor)->for($campaign)->create([
        'receipt_sent_at' => now()->subMonth(),
    ]);

    $this->artisan(BackfillDonorEmailLogs::class)
        ->expectsConfirmation('This will create donor_email_log records for donations that have receipt_sent_at. Continue?', 'no')
        ->assertFailed();
});
