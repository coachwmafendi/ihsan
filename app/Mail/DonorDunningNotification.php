<?php

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class DonorDunningNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public ?string $loginToken = null;

    public function __construct(
        public Subscription $subscription,
        public int $retryCount,
        public bool $isFinalAttempt = false,
        public ?string $messageId = null,
    ) {
        if ($this->subscription->donor !== null) {
            $this->loginToken = $this->subscription->donor->generateMagicToken();
        }

        if ($this->messageId) {
            $this->metadata('donor_email_log_message_id', $this->messageId);
        }
    }

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
            from: new Address(noreply_email(), $orgName),
            subject: $subject,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            text: $this->messageId ? ['X-Donor-Email-Log-Message-Id' => $this->messageId] : [],
        );
    }

    public function content(): Content
    {
        $donor = $this->subscription->donor;
        $organization = $this->subscription->campaign?->organization;

        return new Content(
            view: 'emails.donor-dunning-notification',
            with: [
                'donor' => $donor,
                'locale' => $this->donorLocale($donor),
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
                'loginUrl' => $donor !== null && $organization !== null && $this->loginToken !== null
                    ? route('donorportal.magic-login', ['organization' => $organization, 'token' => $this->loginToken])
                    : null,
            ],
        );
    }
}
