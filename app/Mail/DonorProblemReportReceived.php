<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

/**
 * Acknowledges a problem report back to the donor who sent it, so they know
 * the message arrived rather than wondering whether the form worked.
 */
class DonorProblemReportReceived extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Donor $donor,
        public Organization $organization,
        public string $reportMessage,
        public ?string $messageId = null,
    ) {
        if ($this->messageId) {
            $this->metadata('donor_email_log_message_id', $this->messageId);
        }
    }

    public function envelope(): Envelope
    {
        $locale = $this->donorLocale($this->donor);

        return new Envelope(
            from: new Address(noreply_email(), $this->organization->name),
            subject: trans('emails.donor_problem_report.subject', [
                'organization' => $this->organization->name,
            ], $locale),
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: $this->messageId ? ['X-Donor-Email-Log-Message-Id' => $this->messageId] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.donor-problem-report-received',
            with: [
                'locale' => $this->donorLocale($this->donor),
                'donor' => $this->donor,
                'organization' => $this->organization,
                'reportMessage' => $this->reportMessage,
            ],
        );
    }
}
