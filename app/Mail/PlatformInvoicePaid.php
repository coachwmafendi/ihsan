<?php

namespace App\Mail;

use App\Models\MonthlyInvoice;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Http\Client\RequestException;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class PlatformInvoicePaid extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public MonthlyInvoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Platform Fee Paid — '.Carbon::parse($this->invoice->period)->format('F Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.platform-invoice-paid',
        );
    }

    public function attachments(): array
    {
        $pdf = $this->downloadInvoicePdf();

        if ($pdf === null) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $pdf, $this->invoice->invoice_number.'.pdf')
                ->withMime('application/pdf'),
        ];
    }

    private function downloadInvoicePdf(): ?string
    {
        if (! $this->invoice->stripe_invoice_pdf) {
            return null;
        }

        try {
            return Http::timeout(15)
                ->get($this->invoice->stripe_invoice_pdf)
                ->throw()
                ->body();
        } catch (RequestException $e) {
            report($e);

            return null;
        }
    }
}
