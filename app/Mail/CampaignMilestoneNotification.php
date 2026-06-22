<?php

namespace App\Mail;

use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignMilestoneNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public string $milestone,
        public string $percent,
        public string $collected,
        public string $target,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Campaign Milestone Reached — '.$this->campaign->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign-milestone-notification',
        );
    }
}
