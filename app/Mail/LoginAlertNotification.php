<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Organization;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class LoginAlertNotification extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailReference;

    public function __construct(
        public Organization $organization,
        public string $country,
        public string $ipAddress,
        public string $browser,
        public CarbonImmutable $loggedInAt,
        public string $ipType = 'IPv4',
        public ?string $ipv4Address = null,
        public string $city = '',
        public string $region = '',
        public string $isp = '',
    ) {
        $this->emailReference = strtoupper(Str::random(8));
    }

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
            with: [
                'emailReference' => $this->emailReference,
            ],
        );
    }
}
