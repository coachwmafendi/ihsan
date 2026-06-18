<?php

namespace App\Mail;

use App\Models\Donation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DonationReceipt extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Donation $donation,
    ) {}

    public function envelope(): Envelope
    {
        $org = $this->donation->campaign?->organization;
        $orgName = $org?->name ?? config('app.name');
        $replyTo = $org?->settings['portal_reply_to_email'] ?? null;

        return new Envelope(
            from: new Address(config('mail.from.address', 'no-reply@getihsan.my'), $orgName),
            subject: 'Your Donation Receipt — '.$orgName,
            replyTo: $replyTo ? [new Address($replyTo, $org->name)] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.donation-receipt',
        );
    }

    public function attachments(): array
    {
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
