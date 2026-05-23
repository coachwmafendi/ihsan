<?php

namespace App\Mail;

use App\Models\MonthlyInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformInvoiceCreated extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonthlyInvoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Platform Fee Invoice — '.$this->invoice->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.platform-invoice-created',
        );
    }
}
