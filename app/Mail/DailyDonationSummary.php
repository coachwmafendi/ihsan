<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDonationSummary extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public int $donationCount,
        public string $totalAmount,
        public array $campaigns,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Daily Donation Summary — '.now()->format('d M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.daily-donation-summary',
        );
    }
}
