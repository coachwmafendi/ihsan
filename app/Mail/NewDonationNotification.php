<?php

namespace App\Mail;

use App\Enums\DonationType;
use App\Models\Donation;
use App\Support\Currency;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDonationNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation,
        public string $amountDisplay,
    ) {}

    public function envelope(): Envelope
    {
        $donorName = $this->donation->donor?->name ?? 'Someone';
        $campaignTitle = $this->donation->campaign?->title ?? 'your campaign';

        if ($this->donation->type === DonationType::Recurring) {
            $paymentNumber = $this->donation->subscription?->payment_count ?? 1;
            $ordinalSuffix = match ($paymentNumber % 100) {
                11, 12, 13 => 'th',
                default => match ($paymentNumber % 10) {
                    1 => 'st',
                    2 => 'nd',
                    3 => 'rd',
                    default => 'th',
                },
            };
            $ordinal = $paymentNumber.$ordinalSuffix;

            $symbol = Currency::symbol($this->donation->currency);
            $originalAmount = $symbol.' '.number_format((float) $this->donation->total_charged, 2);
            $conversionAppend = '';

            if (strtolower($this->donation->currency) !== 'myr' && $this->donation->base_amount !== null) {
                $exchangeRate = (float) ($this->donation->exchange_rate ?? 0);
                $feeInBase = $exchangeRate > 0
                    ? round((float) $this->donation->donor_fee_covered * $exchangeRate, 2)
                    : (float) $this->donation->donor_fee_covered;
                $conversionAppend = ' (≈MYR '.number_format((float) $this->donation->base_amount + $feeInBase, 2).')';
            }

            $amount = $originalAmount.$conversionAppend;

            return new Envelope(
                subject: "{$ordinal} recurring {$amount} donation by {$donorName} on {$campaignTitle}",
            );
        }

        $amount = $this->donation->total_charged_with_conversion;

        return new Envelope(
            subject: "New one-time donation {$amount} by {$donorName} on {$campaignTitle} — ".config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.new-donation-notification',
        );
    }
}
