<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewSubscriptionNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation,
        public string $amountDisplay,
    ) {}

    public function envelope(): Envelope
    {
        $donorName = $this->donation->donor?->name ?? 'Someone';
        $campaignTitle = $this->donation->campaign?->title ?? 'your campaign';

        return new Envelope(
            subject: "New Recurring Subscription {$this->amountDisplay} by {$donorName} on {$campaignTitle} — ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-subscription-notification',
        );
    }
}
