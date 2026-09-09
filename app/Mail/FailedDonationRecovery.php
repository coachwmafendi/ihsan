<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Donation;
use App\Support\PaymentFailureReason;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * A donation that the bank refused, and what the donor can do about it.
 *
 * Sent once, an hour after the failure, and only when nobody has given since.
 * Most people who fail retry within minutes on their own; this is for the ones
 * who closed the tab and would otherwise never learn why nothing happened.
 */
class FailedDonationRecovery extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Donation $donation,
        public PaymentFailureReason $reason,
        public string $retryUrl,
        public ?string $messageId = null,
    ) {}

    /**
     * The id the SES webhook matches a delivery back to. Without it the log
     * entry never leaves "queued", and for a letter whose whole purpose is
     * reaching somebody, not knowing whether it arrived is the one thing we
     * cannot afford - the two sent so far are both still unaccounted for.
     */
    public function headers(): Headers
    {
        return new Headers(
            text: $this->messageId ? ['X-Donor-Email-Log-Message-Id' => $this->messageId] : [],
        );
    }

    public function envelope(): Envelope
    {
        $organization = $this->donation->campaign?->organization;

        return new Envelope(
            from: new Address(noreply_email(), $organization?->name ?? config('app.name')),
            replyTo: filled($organization?->contact_email)
                ? [new Address($organization->contact_email, $organization->name)]
                : [],
            subject: trans('emails.failed_donation.subject', [], $this->donorLocale($this->donation->donor)),
        );
    }

    public function content(): Content
    {
        // The donor layout every other supporter email uses, rather than the
        // generic markdown one - which arrived with a black button and none of
        // the organisation's own dressing.
        return new Content(
            view: 'emails.failed-donation-recovery',
            with: [
                'donor' => $this->donation->donor,
                'locale' => $this->donorLocale($this->donation->donor),
            ],
        );
    }
}
