<?php

declare(strict_types=1);

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Jobs\SendFailedDonationRecovery;
use App\Mail\FailedDonationRecovery;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * Two thirds of failed donations are retried successfully by the donor within
 * the day. This is for the third that closed the tab, and every guard here
 * exists to keep it away from the two thirds that did not need it.
 */
beforeEach(function () {
    Mail::fake();

    $this->organization = Organization::factory()->create(['contact_email' => 'ngo@example.test']);
    $this->campaign = Campaign::factory()->for($this->organization)->create();
    $this->donor = Donor::factory()->create(['email' => 'donor@example.test', 'name' => 'Aminah']);
});

function failedDonation(array $attributes = [], ?array $error = null): Donation
{
    return Donation::factory()->for(test()->campaign)->create(array_merge([
        'donor_id' => test()->donor->getKey(),
        'status' => DonationStatus::Failed,
        'type' => DonationType::OneTime,
        'gross_amount' => 30,
        'currency' => 'usd',
        'fraud_status' => 'clean',
        'stripe_fee_details' => ['last_payment_error' => $error ?? [
            'message' => 'Your card does not support this type of purchase.',
            'decline_code' => 'transaction_not_allowed',
            'code' => 'card_declined',
        ]],
    ], $attributes));
}

it('writes to a donor whose payment was refused', function () {
    $donation = failedDonation();

    (new SendFailedDonationRecovery($donation->getKey()))->handle();

    Mail::assertQueued(FailedDonationRecovery::class, function (FailedDonationRecovery $mail) use ($donation) {
        return $mail->hasTo('donor@example.test')
            && $mail->reason->code === 'transaction_not_allowed'
            // The choices they already made, so picking up again is a couple of taps.
            && str_contains($mail->retryUrl, 'amount=30')
            && str_contains($mail->retryUrl, 'currency=usd')
            && $mail->donation->is($donation);
    });

    expect(DonorEmailLog::where('mailable_class', FailedDonationRecovery::class)->count())->toBe(1);
});

it('says nothing to a donor who has since given to the same campaign', function () {
    // The common case. Writing to them reads as the platform being broken.
    $donation = failedDonation();

    Donation::factory()->for($this->campaign)->create([
        'donor_id' => $this->donor->getKey(),
        'status' => DonationStatus::Succeeded,
        'created_at' => $donation->created_at->addMinutes(2),
    ]);

    (new SendFailedDonationRecovery($donation->getKey()))->handle();

    Mail::assertNothingQueued();
});

it('writes once however many times the same card was refused', function () {
    // A Brunei donor tried the same prepaid card three times in ninety seconds.
    $attempts = collect(range(1, 3))->map(fn () => failedDonation());

    $attempts->each(fn (Donation $donation) => (new SendFailedDonationRecovery($donation->getKey()))->handle());

    Mail::assertQueuedCount(1);
});

it('leaves a donation the fraud checks were unhappy about alone', function () {
    $donation = failedDonation(['fraud_status' => 'flagged']);

    (new SendFailedDonationRecovery($donation->getKey()))->handle();

    Mail::assertNothingQueued();
});

it('stays quiet when there is no reason to give', function () {
    // Without one the mail would say only that something went wrong, which the
    // donor already knows.
    $donation = failedDonation(['stripe_fee_details' => []]);

    (new SendFailedDonationRecovery($donation->getKey()))->handle();

    Mail::assertNothingQueued();
});

it('does nothing for a donation that is no longer failed', function () {
    // An hour is long enough for the facts to change underneath the job.
    $donation = failedDonation(['status' => DonationStatus::Succeeded]);

    (new SendFailedDonationRecovery($donation->getKey()))->handle();

    Mail::assertNothingQueued();
});

it('offers another card rather than a retry when the same one cannot work', function () {
    (new SendFailedDonationRecovery(failedDonation()->getKey()))->handle();

    Mail::assertQueued(FailedDonationRecovery::class, function (FailedDonationRecovery $mail) {
        $body = strip_tags($mail->render());

        return str_contains($body, 'Give with another card')
            && ! str_contains($body, 'Try again');
    });
});

it('offers a retry when the same card may well work next time', function () {
    $donation = failedDonation(error: [
        'message' => 'Your card has insufficient funds.',
        'decline_code' => 'insufficient_funds',
        'code' => 'card_declined',
    ]);

    (new SendFailedDonationRecovery($donation->getKey()))->handle();

    Mail::assertQueued(FailedDonationRecovery::class, function (FailedDonationRecovery $mail) {
        return str_contains(strip_tags($mail->render()), 'Try again');
    });
});
