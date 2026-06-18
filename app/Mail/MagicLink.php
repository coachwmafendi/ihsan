<?php

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLink extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donor $donor,
        public string $token,
        public Organization $organization,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Donation Portal Login Link — '.$this->organization->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.magic-link',
            with: [
                'donor' => $this->donor,
                'unsubscribeUrl' => DonorNotificationController::unsubscribeUrl($this->donor),
            ],
        );
    }
}
