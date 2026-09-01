<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The problem report itself, for whoever runs the platform.
 *
 * Replies go to the donor, so an operator can answer from their own mail
 * client without exposing the platform's sending address.
 */
class DonorProblemReportNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donor $donor,
        public Organization $organization,
        public string $reportMessage,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(noreply_email(), config('app.name')),
            replyTo: [new Address($this->donor->email, $this->donor->name ?? '')],
            subject: 'Report a Problem — '.$this->organization->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.donor-problem-report-notification',
        );
    }
}
