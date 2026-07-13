<?php

declare(strict_types=1);

namespace App\Mail;

use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Subscription;
use App\Support\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class SupporterSubscriptionAmountChangedNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Subscription $subscription,
        public float $previousAmount,
        public ?string $messageId = null,
    ) {
        if ($this->messageId) {
            $this->metadata('donor_email_log_message_id', $this->messageId);
        }
    }

    public function envelope(): Envelope
    {
        $org = $this->subscription->campaign?->organization;
        $orgName = $org?->name ?? config('app.name');
        $locale = $this->donorLocale($this->subscription->donor);

        return new Envelope(
            from: new Address(noreply_email(), $orgName),
            subject: trans('emails.supporter_subscription_amount_changed.subject', ['name' => $orgName], $locale),
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
        $locale = $this->donorLocale($donor);

        return new Content(
            view: 'emails.supporter-subscription-amount-changed-notification',
            with: [
                'donor' => $donor,
                'locale' => $locale,
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
                'previousAmountDisplay' => $this->previousAmountDisplay(),
                'currentAmountDisplay' => $this->currentAmountDisplay(),
                'upgradeChips' => $this->upgradeChips($locale),
            ],
        );
    }

    private function previousAmountDisplay(): string
    {
        $symbol = $this->currencySymbol();

        return $symbol.' '.number_format($this->previousAmount, 2);
    }

    private function currentAmountDisplay(): string
    {
        $symbol = $this->currencySymbol();

        return $symbol.' '.number_format((float) $this->subscription->amount, 2);
    }

    private function currencySymbol(): string
    {
        return $this->subscription->currency_symbol ?? Currency::symbol(strtoupper($this->subscription->currency));
    }

    private function upgradeChips(string $locale): ?array
    {
        $subscription = $this->subscription;
        $organization = $subscription->campaign?->organization;

        if ($organization === null) {
            return null;
        }

        $symbol = $this->currencySymbol();
        $shortInterval = $subscription->interval->shortLabel($locale);
        $expires = now()->addDays(7);

        $increments = [15, 25, 35];
        $incrementsQuery = implode(',', $increments);

        return collect($increments)
            ->map(function (int $increment) use ($symbol, $shortInterval, $expires, $organization, $subscription, $incrementsQuery): array {
                return [
                    'label' => "+ {$symbol}{$increment}/{$shortInterval}",
                    'url' => URL::temporarySignedRoute(
                        'donorportal.subscriptions.increase-link',
                        $expires,
                        [
                            'organization' => $organization,
                            'subscription' => $subscription,
                            'increments' => $incrementsQuery,
                            'selected' => (string) $increment,
                        ],
                    ),
                ];
            })
            ->all();
    }
}
