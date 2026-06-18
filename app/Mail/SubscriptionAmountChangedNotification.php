<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SubscriptionAmountChangedNotification extends Mailable
{
    use Queueable, SerializesModels;

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

        return new Envelope(
            from: $this->isDonor
                ? new Address(config('mail.from.address', 'no-reply@getihsan.my'), $orgName)
                : null,
            subject: 'Subscription Amount Updated — '.$orgName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.subscription-amount-changed-notification',
        );
    }
}
