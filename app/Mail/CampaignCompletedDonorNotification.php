<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Donor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignCompletedDonorNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public Donor $donor,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎉 Target Tercapai — '.$this->campaign->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign-completed-donor-notification',
        );
    }
}
