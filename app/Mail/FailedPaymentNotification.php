<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FailedPaymentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public ?string $failureMessage = null,
        public bool $isFinalAttempt = false,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Failed Payment Notification — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.failed-payment-notification',
        );
    }
}
