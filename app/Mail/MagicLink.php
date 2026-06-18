<?php

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MagicLink extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Donor $donor,
        public string $token,
        public Organization $organization,
    ) {}

    public function envelope(): Envelope
    {
        $locale = $this->donorLocale($this->donor);

        return new Envelope(
            from: new Address(config('mail.from.address', 'no-reply@getihsan.my'), $this->organization->name),
            subject: trans('emails.magic_link.title', ['name' => $this->organization->name], $locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.magic-link',
            with: [
                'donor' => $this->donor,
                'locale' => $this->donorLocale($this->donor),
                'unsubscribeUrl' => DonorNotificationController::unsubscribeUrl($this->donor),
            ],
        );
    }
}
