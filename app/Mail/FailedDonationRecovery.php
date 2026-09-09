<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Donation;
use App\Support\PaymentFailureReason;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation,
        public PaymentFailureReason $reason,
        public string $retryUrl,
    ) {}

    public function envelope(): Envelope
    {
        $organization = $this->donation->campaign?->organization;

        return new Envelope(
            from: new Address(noreply_email(), $organization?->name ?? config('app.name')),
            replyTo: filled($organization?->contact_email)
                ? [new Address($organization->contact_email, $organization->name)]
                : [],
            subject: 'Your donation did not go through',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.failed-donation-recovery',
            with: [
                'donorName' => $this->donation->donor?->name,
                'campaignTitle' => $this->donation->campaign?->title,
                'organizationName' => $this->donation->campaign?->organization?->name,
                'amount' => $this->donation->display_donation_amount,
            ],
        );
    }
}
