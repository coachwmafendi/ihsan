<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Campaign;
use App\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Something went wrong in the checkout and the person it happened to told us.
 *
 * There is no donor record to attach: they never got far enough to make one,
 * which is the whole point of the report. So there is nobody to reply to, and
 * the message has to carry enough about the page itself to be worth reading.
 */
class CheckoutProblemReport extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Organization $organization,
        public Campaign $campaign,
        public string $reportMessage,
        public ?string $pageUrl = null,
        public ?string $deviceType = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(noreply_email(), config('app.name')),
            subject: 'Checkout problem reported — '.$this->campaign->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.checkout-problem-report',
        );
    }
}
