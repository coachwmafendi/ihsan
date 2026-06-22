<?php

namespace App\Mail;

use App\Enums\DonationType;
use App\Http\Controllers\DonorNotificationController;
use App\Mail\Concerns\SetsDonorLocale;
use App\Models\Donation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class DonationReceipt extends Mailable
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
        $replyTo = $org?->settings['portal_reply_to_email'] ?? null;
        $locale = $this->donorLocale($this->donation->donor);

        return new Envelope(
            from: new Address(config('mail.from.address', 'no-reply@getihsan.my'), $orgName),
            subject: trans('emails.receipt.subject', ['name' => $orgName], $locale),
            replyTo: $replyTo ? [new Address($replyTo, $org->name)] : [],
        );
    }

    public function content(): Content
    {
        $donor = $this->donation->donor;

        return new Content(
            view: 'emails.donation-receipt',
            with: [
                'donor' => $donor,
                'locale' => $this->donorLocale($donor),
                'unsubscribeUrl' => $donor ? DonorNotificationController::unsubscribeUrl($donor) : null,
                'downloadUrl' => $this->signedReceiptUrl(),
            ],
        );
    }

    private function signedReceiptUrl(): ?string
    {
        if ($this->donation->type !== DonationType::Recurring) {
            return null;
        }

        if ($this->donation->status->value !== 'succeeded') {
            return null;
        }

        return URL::signedRoute('receipts.signed', ['donation' => $this->donation], now()->addDays(30));
    }

    public function headers(): Headers
    {
        return new Headers(
            text: $this->messageId ? ['X-Donor-Email-Log-Message-Id' => $this->messageId] : [],
        );
    }

    public function attachments(): array
    {
        if ($this->donation->type === DonationType::Recurring) {
            return [];
        }

        $pdf = Pdf::loadView('emails.donation-receipt-pdf', [
            'donation' => $this->donation,
        ]);

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                config('app.name').'-'.$this->donation->campaign->organization->code.'-'.$this->donation->invoice_number.'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
