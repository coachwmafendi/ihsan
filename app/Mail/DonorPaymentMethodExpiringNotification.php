<?php

declare(strict_types=1);

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\DonorPaymentMethod;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DonorPaymentMethodExpiringNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public Subscription $primarySubscription;

    public DonorPaymentMethod $paymentMethod;

    public ?string $loginToken = null;

    /**
     * @param  Collection<int, Subscription>  $subscriptions
     */
    public function __construct(
        public Collection $subscriptions,
        public int $daysRemaining,
        public ?string $messageId = null,
    ) {
        $this->primarySubscription = $subscriptions->first();
        $this->paymentMethod = $this->primarySubscription->donorPaymentMethod;

        if ($this->primarySubscription->donor !== null) {
            $this->loginToken = $this->primarySubscription->donor->generateMagicToken();
        }

        if ($this->messageId) {
            $this->metadata('donor_email_log_message_id', $this->messageId);
        }
    }

    public function envelope(): Envelope
    {
        $organization = $this->primarySubscription->campaign?->organization;
        $locale = $this->donorLocale($this->primarySubscription->donor);

        return new Envelope(
            from: new Address(
                noreply_email(),
                $organization?->name ?? config('app.name')
            ),
            subject: trans('emails.donor_payment_method_expiring.subject', [
                'days' => $this->daysRemaining,
                'organization' => $organization?->name ?? config('app.name'),
            ], $locale),
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
        $donor = $this->primarySubscription->donor;
        $organization = $this->primarySubscription->campaign?->organization;

        return new Content(
            view: 'emails.donor-payment-method-expiring-notification',
            with: [
                'donor' => $donor,
                'locale' => $this->donorLocale($donor),
                'daysRemaining' => $this->daysRemaining,
                'subscriptions' => $this->subscriptions,
                'paymentMethod' => $this->paymentMethod,
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
                'loginUrl' => $donor !== null && $organization !== null && $this->loginToken !== null
                    ? route('donorportal.magic-login', [
                        'organization' => $organization,
                        'token' => $this->loginToken,
                        'redirect' => '/donorportal/'.$organization->code.'/subscriptions',
                    ])
                    : null,
            ],
        );
    }
}
