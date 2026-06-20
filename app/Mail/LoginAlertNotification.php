<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginAlertNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public string $country,
        public string $ipAddress,
        public string $browser,
        public CarbonImmutable $loggedInAt,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New login to your '.config('app.name').' account',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.login-alert-notification',
        );
    }
}
