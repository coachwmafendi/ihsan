<?php

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionAmountChangedNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Subscription $subscription,
        public float $previousAmount,
        public string $amountDisplay,
        public bool $isDonor = false,
    ) {}

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

    public function content(): Content
    {
        $donor = $this->subscription->donor;

        return new Content(
            view: 'emails.subscription-amount-changed-notification',
            with: [
                'donor' => $donor,
                'locale' => $this->isDonor
                    ? $this->donorLocale($donor)
                    : config('app.locale'),
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
            ],
        );
    }
}
