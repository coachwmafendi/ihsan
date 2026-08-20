<?php

declare(strict_types=1);

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\DonorPaymentMethod;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class DonorPaymentMethodChangedNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public ?string $loginToken = null;

    public function __construct(
        public Subscription $subscription,
        public ?DonorPaymentMethod $previousPaymentMethod,
        public DonorPaymentMethod $newPaymentMethod,
        public ?string $messageId = null,
    ) {
        if ($this->subscription->donor !== null) {
            $this->loginToken = $this->subscription->donor->generateMagicToken();
        }

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
                noreply_email(),
                $organization?->name ?? config('app.name')
            ),
            subject: trans('emails.donor_payment_method_changed.subject', [
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
        $organization = $this->subscription->campaign?->organization;

        return new Content(
            view: 'emails.donor-payment-method-changed-notification',
            with: [
                'donor' => $donor,
                'locale' => $this->donorLocale($donor),
                'previousPaymentMethod' => $this->previousPaymentMethod,
                'newPaymentMethod' => $this->newPaymentMethod,
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
                'loginUrl' => $donor !== null && $organization !== null && $this->loginToken !== null
                    ? route('donorportal.magic-login', [
                        'organization' => $organization,
                        'token' => $this->loginToken,
                        'redirect' => '/donorportal/'.$organization->code.'/subscriptions',
                    ])
                    : null,
            ],
        );
    }
}
