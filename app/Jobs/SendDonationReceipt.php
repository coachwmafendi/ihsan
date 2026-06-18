<?php

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
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

        $mailable = new DonationReceipt($this->donation);

        Mail::to($this->donation->donor->email)
            ->send($mailable);

        app(LogDonorEmail::class)->handle(
            donor: $this->donation->donor,
            mailable: $mailable,
            organization: $this->donation->campaign?->organization,
            donation: $this->donation,
        );

        $this->donation->update(['receipt_sent_at' => now()]);
    }
}
