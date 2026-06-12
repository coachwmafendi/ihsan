<?php

namespace App\Mail;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonorDunningNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public int $retryCount,
        public bool $isFinalAttempt = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = match (true) {
            $this->isFinalAttempt => 'Last Chance to Update Payment — '.config('app.name'),
            $this->retryCount >= 3 => 'Final Attempt Tomorrow — '.config('app.name'),
            default => 'Payment Failed — Update Your Card — '.config('app.name'),
        };

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.donor-dunning-notification');
    }
}
