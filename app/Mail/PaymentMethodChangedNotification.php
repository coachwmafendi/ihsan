<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\DonorPaymentMethod;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentMethodChangedNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Subscription $subscription,
        public ?DonorPaymentMethod $previousPaymentMethod,
        public DonorPaymentMethod $newPaymentMethod,
    ) {}

    public function envelope(): Envelope
    {
        $organization = $this->subscription->campaign?->organization;

        return new Envelope(
            from: new Address(
                noreply_email(),
                $organization?->name ?? config('app.name')
            ),
            subject: 'Supporter updated their payment method — '.($organization?->name ?? config('app.name')),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment-method-changed-notification',
            with: [
                'subscription' => $this->subscription,
                'previousPaymentMethod' => $this->previousPaymentMethod,
                'newPaymentMethod' => $this->newPaymentMethod,
            ],
        );
    }
}
