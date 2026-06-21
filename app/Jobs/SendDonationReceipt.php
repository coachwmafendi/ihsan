<?php

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonationReceipt;
use App\Models\Donation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendDonationReceipt implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donation $donation,
        public bool $force = false,
    ) {}

    public function handle(): void
    {
        $donation = Donation::query()->whereKey($this->donation->getKey())->first();

        if ($donation === null) {
            return;
        }

        $donation->load(['donor', 'campaign.organization']);

        if ((float) $donation->gross_amount <= 0) {
            return;
        }

        if (! $this->force && $donation->receipt_sent_at !== null) {
            return;
        }

        if (! $this->force) {
            $claimed = Donation::query()
                ->whereKey($donation->getKey())
                ->whereNull('receipt_sent_at')
                ->update(['receipt_sent_at' => now()]);

            if ($claimed === 0) {
                return;
            }

            $donation->refresh();
        }

        $messageId = Str::uuid()->toString();
        $mailable = new DonationReceipt($donation, $messageId);

        app(LogDonorEmail::class)->handle(
            donor: $donation->donor,
            mailable: $mailable,
            organization: $donation->campaign?->organization,
            donation: $donation,
            messageId: $messageId,
        );

        Mail::to($donation->donor->email)
            ->send($mailable);

        $donation->update(['receipt_sent_at' => now()]);
    }
}
