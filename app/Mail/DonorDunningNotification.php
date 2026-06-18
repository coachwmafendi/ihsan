<?php

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonorDunningNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public int $retryCount,
        public bool $isFinalAttempt = false,
    ) {}

    public function envelope(): Envelope
    {
        $org = $this->subscription->campaign?->organization;
        $orgName = $org?->name ?? config('app.name');

        $subject = match (true) {
            $this->isFinalAttempt => 'Last Chance to Update Payment — '.$orgName,
            $this->retryCount >= 3 => 'Final Attempt Tomorrow — '.$orgName,
            default => 'Payment Failed — Update Your Card — '.$orgName,
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
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
            ],
        );
    }
}
