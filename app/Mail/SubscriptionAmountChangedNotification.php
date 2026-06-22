<?php

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class SubscriptionAmountChangedNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public string $emailReference;

    public function __construct(
        public Subscription $subscription,
        public float $previousAmount,
        public string $amountDisplay,
        public bool $isDonor = false,
        public ?string $messageId = null,
        public ?User $admin = null,
    ) {
        $this->emailReference = strtoupper(Str::random(8));

        if ($this->messageId) {
            $this->metadata('donor_email_log_message_id', $this->messageId);
        }
    }

    public function envelope(): Envelope
    {
        $org = $this->subscription->campaign?->organization;
        $orgName = $org?->name ?? config('app.name');
        $locale = $this->isDonor
            ? $this->donorLocale($this->subscription->donor)
            : config('app.locale');

        return new Envelope(
            from: $this->isDonor
                ? new Address(config('mail.from.address', 'no-reply@getihsan.my'), $orgName)
                : null,
            subject: trans('emails.subscription_amount_changed.subject', ['name' => $orgName], $locale),
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

        return new Content(
            view: 'emails.subscription-amount-changed-notification',
            with: [
                'donor' => $donor,
                'admin' => $this->admin,
                'emailReference' => $this->emailReference,
                'locale' => $this->isDonor
                    ? $this->donorLocale($donor)
                    : config('app.locale'),
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
            ],
        );
    }
}
