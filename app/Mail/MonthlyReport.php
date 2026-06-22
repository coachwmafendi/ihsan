<?php

namespace App\Mail;

use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MonthlyReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public int $donationCount,
        public string $totalAmount,
        public array $campaigns,
        public string $period,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Monthly Donation Report — '.$this->period,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.monthly-report',
        );
    }
}
