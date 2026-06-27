<?php

declare(strict_types=1);

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class DonorSubscriptionCancelledNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Subscription $subscription,
        public ?string $messageId = null,
    ) {
        if ($this->messageId) {
            $this->metadata('donor_email_log_message_id', $this->messageId);
        }
    }

    public function envelope(): Envelope
    {
        $organization = $this->subscription->campaign?->organization;
        $locale = $this->donorLocale($this->subscription->donor);

        return new Envelope(
            from: new Address(
                config('mail.from.address', 'no-reply@getihsan.my'),
                $organization?->name ?? config('app.name')
            ),
            subject: trans('emails.donor_subscription_cancelled.subject', [
                'organization' => $organization?->name ?? config('app.name'),
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
        $donor = $this->subscription->donor;
        $locale = $this->donorLocale($donor);

        return new Content(
            view: 'emails.donor-subscription-cancelled-notification',
            with: [
                'donor' => $donor,
                'locale' => $locale,
                'unsubscribeUrl' => DonorNotificationController::unsubscribeUrl($donor),
            ],
        );
    }
}
