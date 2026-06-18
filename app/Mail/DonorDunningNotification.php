<?php

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonorDunningNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Subscription $subscription,
        public int $retryCount,
        public bool $isFinalAttempt = false,
    ) {}

    public function envelope(): Envelope
    {
        $org = $this->subscription->campaign?->organization;
        $orgName = $org?->name ?? config('app.name');
        $locale = $this->donorLocale($this->subscription->donor);

        $subject = match (true) {
            $this->isFinalAttempt => trans('emails.dunning.subject_final', ['name' => $orgName], $locale),
            $this->retryCount >= 3 => trans('emails.dunning.subject_almost_final', ['name' => $orgName], $locale),
            default => trans('emails.dunning.subject_default', ['name' => $orgName], $locale),
        };

        return new Envelope(
            from: new Address(config('mail.from.address', 'no-reply@getihsan.my'), $orgName),
            subject: $subject,
        );
    }

    public function content(): Content
    {
        $donor = $this->subscription->donor;

        return new Content(
            view: 'emails.donor-dunning-notification',
            with: [
                'donor' => $donor,
                'locale' => $this->donorLocale($donor),
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
            ],
        );
    }
}
