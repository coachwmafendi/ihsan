<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Enums\DonationStatus;
use App\Mail\FailedDonationRecovery;
use App\Models\Donation;
use App\Models\DonorEmailLog;
use App\Support\PaymentFailureReason;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Tell a donor their payment was refused, once, an hour after it happened.
 *
 * Two thirds of failed donations are retried successfully by the donor within
 * the day, usually within minutes - so an hour is late enough that the people
 * who were going to fix it themselves already have, and early enough that the
 * ones who gave up have not forgotten why they came.
 *
 * Everything here is a reason not to send. The delay is long enough for the
 * facts to change underneath it, so every guard is checked at the moment of
 * sending rather than at the moment of scheduling.
 */
class SendFailedDonationRecovery implements ShouldQueue
{
    use Queueable;

    /**
     * How long a donor is left alone after one of these, for one campaign.
     */
    private const QuietDays = 30;

    public function __construct(public int $donationId) {}

    public function handle(): void
    {
        $donation = Donation::query()
            ->with(['donor', 'campaign.organization'])
            ->find($this->donationId);

        if ($donation === null || $donation->status !== DonationStatus::Failed) {
            return;
        }

        // Anything the fraud checks were unhappy about is not something to
        // encourage a second attempt at.
        if ($donation->fraud_status !== 'clean') {
            return;
        }

        $donor = $donation->donor;
        $campaign = $donation->campaign;
        $organization = $campaign?->organization;

        if ($donor === null || blank($donor->email) || $campaign === null || $organization === null) {
            return;
        }

        if ($organization->trashed()) {
            return;
        }

        $reason = PaymentFailureReason::for($donation);

        // With no reason to give, the mail would say only that something went
        // wrong, which the donor already knows.
        if ($reason === null) {
            return;
        }

        if ($this->donorHasSinceGiven($donation)) {
            return;
        }

        if ($this->alreadyWrittenRecently($donation)) {
            return;
        }

        $mailable = new FailedDonationRecovery($donation, $reason, $this->retryUrl($donation));

        Mail::to($donor->email, $donor->name)->queue($mailable);

        app(LogDonorEmail::class)->handle(
            donor: $donor,
            mailable: $mailable,
            organization: $organization,
            donation: $donation,
            metadata: ['decline_code' => $reason->code],
        );
    }

    /**
     * The donor fixed it themselves, on this campaign, after this attempt - the
     * common case, and the one where writing to them reads as a mistake.
     */
    private function donorHasSinceGiven(Donation $donation): bool
    {
        return Donation::query()
            ->where('donor_id', $donation->donor_id)
            ->where('campaign_id', $donation->campaign_id)
            ->where('status', DonationStatus::Succeeded)
            ->where('created_at', '>=', $donation->created_at)
            ->exists();
    }

    /**
     * Three declines in ninety seconds is one donor having a bad time, not
     * three people to write to.
     */
    private function alreadyWrittenRecently(Donation $donation): bool
    {
        return DonorEmailLog::query()
            ->where('donor_id', $donation->donor_id)
            ->where('mailable_class', FailedDonationRecovery::class)
            ->where('sent_at', '>=', now()->subDays(self::QuietDays))
            ->whereHas('donation', fn ($query) => $query->where('campaign_id', $donation->campaign_id))
            ->exists();
    }

    /**
     * Back to the same checkout with the same choices already made, so picking
     * up where they left off is a couple of taps rather than a form again.
     */
    private function retryUrl(Donation $donation): string
    {
        return route('donations.campaign-show', [
            'campaign' => $donation->campaign,
            'amount' => (float) $donation->gross_amount,
            'currency' => $donation->currency,
            'frequency' => $donation->type->value === 'recurring' ? 'monthly' : 'one_time',
        ]);
    }
}
