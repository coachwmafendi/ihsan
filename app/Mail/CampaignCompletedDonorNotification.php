<?php

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Campaign;
use App\Models\Donor;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignCompletedDonorNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Campaign $campaign,
        public Donor $donor,
    ) {}

    public function envelope(): Envelope
    {
        $org = $this->campaign->organization;
        $orgName = $org?->name ?? config('app.name');
        $locale = $this->donorLocale($this->donor);

        return new Envelope(
            from: new Address(config('mail.from.address', 'no-reply@getihsan.my'), $orgName),
            subject: trans('emails.campaign_completed.subject', ['campaign' => $this->campaign->title], $locale),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign-completed-donor-notification',
            with: [
                'donor' => $this->donor,
                'locale' => $this->donorLocale($this->donor),
                'unsubscribeUrl' => DonorNotificationController::unsubscribeUrl($this->donor),
            ],
        );
    }
}
