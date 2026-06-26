<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Donation;
use App\Support\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class DonorRecurringPaymentNotification extends Mailable
{
    use Queueable, SerializesModels, SetsDonorLocale;

    public function __construct(
        public Donation $donation,
        public ?string $messageId = null,
    ) {
        if ($this->messageId) {
            $this->metadata('donor_email_log_message_id', $this->messageId);
        }
    }

    public function envelope(): Envelope
    {
        $org = $this->donation->campaign?->organization;
        $orgName = $org?->name ?? config('app.name');
        $locale = $this->donorLocale($this->donation->donor);

        return new Envelope(
            from: new Address(config('mail.from.address', 'no-reply@getihsan.my'), $orgName),
            subject: trans('emails.donor_recurring_payment.subject', ['name' => $orgName], $locale),
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
        $donor = $this->donation->donor;

        $locale = $this->donorLocale($donor);

        return new Content(
            view: 'emails.donor-recurring-payment-notification',
            with: [
                'donor' => $donor,
                'locale' => $locale,
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
                'downloadUrl' => $this->signedReceiptUrl(),
                'upgradeChips' => $this->upgradeChips($locale),
                'currentAmountDisplay' => $this->currentAmountDisplay(),
            ],
        );
    }

    private function upgradeChips(string $locale): ?array
    {
        $subscription = $this->donation->subscription;
        $organization = $this->donation->campaign?->organization;

        if ($subscription === null || $organization === null) {
            return null;
        }

        $currency = strtoupper($subscription->currency);
        $symbol = $subscription->currency_symbol ?? Currency::symbol($currency);
        $shortInterval = $this->intervalShortLabel($subscription->interval, $locale);
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

    private function intervalShortLabel(SubscriptionInterval $interval, string $locale): string
    {
        return match ($interval) {
            SubscriptionInterval::Weekly => trans('emails.donor_recurring_payment.upgrade_interval_short_weekly', [], $locale),
            SubscriptionInterval::Biweekly => trans('emails.donor_recurring_payment.upgrade_interval_short_biweekly', [], $locale),
            SubscriptionInterval::Monthly => trans('emails.donor_recurring_payment.upgrade_interval_short_monthly', [], $locale),
            SubscriptionInterval::Bimonthly => trans('emails.donor_recurring_payment.upgrade_interval_short_bimonthly', [], $locale),
            SubscriptionInterval::Quarterly => trans('emails.donor_recurring_payment.upgrade_interval_short_quarterly', [], $locale),
            SubscriptionInterval::Semiannual => trans('emails.donor_recurring_payment.upgrade_interval_short_semiannual', [], $locale),
            SubscriptionInterval::Yearly => trans('emails.donor_recurring_payment.upgrade_interval_short_yearly', [], $locale),
            default => trans('emails.donor_recurring_payment.upgrade_interval_short_default', ['interval' => $interval->value], $locale),
        };
    }

    private function currentAmountDisplay(): ?string
    {
        $subscription = $this->donation->subscription;

        if ($subscription === null) {
            return null;
        }

        $symbol = $subscription->currency_symbol ?? Currency::symbol(strtoupper($subscription->currency));

        return $symbol.' '.number_format((float) $subscription->amount, 2);
    }

    private function signedReceiptUrl(): ?string
    {
        if ($this->donation->type !== DonationType::Recurring) {
            return null;
        }

        if ($this->donation->status !== DonationStatus::Succeeded) {
            return null;
        }

        return URL::signedRoute('receipts.signed', ['donation' => $this->donation], now()->addDays(30));
    }
}
