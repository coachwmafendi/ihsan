<?php

namespace App\Mail;

use App\Enums\DonationType;
use App\Models\Donation;
use Illuminate\Bus\Queueable;
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

        if ($this->donation->type === DonationType::Recurring) {
            $paymentNumber = $this->donation->subscription?->payment_count ?? 1;
            $ordinalSuffix = match ($paymentNumber % 100) {
                11, 12, 13 => 'th',
                default => match ($paymentNumber % 10) {
                    1 => 'st',
                    2 => 'nd',
                    3 => 'rd',
                    default => 'th',
                },
            };
            $ordinal = $paymentNumber.$ordinalSuffix;

            $amount = $this->donation->display_payment_amount;

            return new Envelope(
                subject: "{$ordinal} recurring {$amount} donation by {$donorName} on {$campaignTitle}",
            );
        }

        $amount = $this->donation->total_charged_with_conversion;

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
