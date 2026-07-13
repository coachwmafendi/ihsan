<?php

declare(strict_types=1);

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class DonorRefundNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Donation $donation,
        public string $amountDisplay,
        public ?string $messageId = null,
    ) {
        if ($this->messageId) {
            $this->metadata('donor_email_log_message_id', $this->messageId);
        }
    }

    public function envelope(): Envelope
    {
        $organization = $this->donation->campaign?->organization;
        $locale = $this->donorLocale($this->donation->donor);

        return new Envelope(
            from: new Address(
                noreply_email(),
                $organization?->name ?? config('app.name')
            ),
            subject: trans('emails.donor_refund.subject', [
                'organization' => $organization?->name ?? config('app.name'),
                'campaign' => $this->donation->campaign?->title,
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
        $donor = $this->donation->donor;
        $locale = $this->donorLocale($donor);

        return new Content(
            view: 'emails.donor-refund-notification',
            with: [
                'donor' => $donor,
                'locale' => $locale,
                'unsubscribeUrl' => DonorNotificationController::unsubscribeUrl($donor),
            ],
        );
    }
}
