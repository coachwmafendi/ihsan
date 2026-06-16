<?php

namespace App\Jobs;

use App\Mail\DonationReceipt;
use App\Models\Donation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDonationReceipt implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donation $donation,
    ) {}

    public function handle(): void
    {
        $this->donation->load(['donor', 'campaign.organization']);

        if ((float) $this->donation->gross_amount <= 0) {
            return;
        }

        Mail::to($this->donation->donor->email)
            ->send(new DonationReceipt($this->donation));

        $this->donation->update(['receipt_sent_at' => now()]);
    }
}
