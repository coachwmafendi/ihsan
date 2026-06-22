<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

class FraudAlertNotification extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Donation $donation,
        public string $reason,
        public string $action,
    ) {}

    public function build(): self
    {
        return $this->subject("Fraud Alert: {$this->action} donation detected")
            ->markdown('emails.fraud-alert', [
                'donation' => $this->donation,
                'reason' => $this->reason,
                'action' => $this->action,
                'url' => route('filament.admin.pages.fraud-prevention'),
            ]);
    }
}
