<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class DonorNewSubscriptionNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Donation $donation,
        public ?string $messageId = null,
    ) {
        if ($this->messageId) {
            $this->metadata('donor_email_log_message_id', $this->messageId);
        }
    }

    public function envelope(): Envelope
    {
        $org = $this->donation->campaign?->organization;
        $orgName = $org?->name ?? config('app.name');
        $locale = $this->donorLocale($this->donation->donor);

        return new Envelope(
            from: new Address(noreply_email(), $orgName),
            subject: trans('emails.donor_new_subscription.subject', ['name' => $orgName], $locale),
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

        return new Content(
            view: 'emails.donor-new-subscription-notification',
            with: [
                'donor' => $donor,
                'locale' => $this->donorLocale($donor),
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
                'downloadUrl' => $this->signedReceiptUrl(),
            ],
        );
    }

    private function signedReceiptUrl(): ?string
    {
        if ($this->donation->type !== DonationType::Recurring) {
            return null;
        }

        if ($this->donation->status !== DonationStatus::Succeeded) {
            return null;
        }

        return $this->donation->receiptUrl();
    }
}
