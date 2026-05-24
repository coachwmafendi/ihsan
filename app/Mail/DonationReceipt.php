<?php

namespace App\Mail;

use App\Models\Donation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
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
        return new Envelope(
            subject: 'Your Donation Receipt — '.config('app.name'),
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
                'donation-receipt-'.$this->donation->id.'.pdf',
            )->withMime('application/pdf'),
        ];
    }
}
