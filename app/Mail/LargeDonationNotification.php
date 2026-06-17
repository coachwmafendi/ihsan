<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LargeDonationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation,
        public string $amountDisplay,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🚨 Large Donation Received — '.$this->subjectAmount().' by '.$this->donorName().' on '.$this->campaignTitle().' — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.large-donation-notification',
        );
    }

    private function subjectAmount(): string
    {
        return $this->donation->total_charged_with_conversion;
    }

    private function donorName(): string
    {
        return $this->donation->donor?->name ?? 'a donor';
    }

    private function campaignTitle(): string
    {
        return $this->donation->campaign?->title ?? 'a campaign';
    }
}
