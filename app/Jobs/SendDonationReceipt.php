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

        Mail::to($this->donation->donor->email)
            ->send(new DonationReceipt($this->donation));

        $this->donation->update(['receipt_sent_at' => now()]);
    }
}
