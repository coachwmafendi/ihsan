<?php

namespace App\Mail;

use App\Enums\DonationType;
use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDonationNotification extends Mailable
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
        $amount = $this->donation->total_charged_with_conversion;

        if ($this->donation->type === DonationType::Recurring) {
            $paymentNumber = $this->donation->subscription?->payment_count ?? 1;

            return new Envelope(
                subject: "Recurring payment #{$paymentNumber} received {$amount} by {$donorName} on {$campaignTitle} — ".config('app.name'),
            );
        }

        return new Envelope(
            subject: "New one-time donation {$amount} by {$donorName} on {$campaignTitle} — ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-donation-notification',
        );
    }
}
