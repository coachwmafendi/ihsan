<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonorRefundNotification;
use App\Models\Donation;
use App\Models\DonorEmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendDonorRefundNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donation $donation,
    ) {}

    public function handle(): void
    {
        $donation = Donation::query()->whereKey($this->donation->getKey())->first();

        if ($donation === null) {
            return;
        }

        $donation->loadMissing(['donor', 'campaign.organization', 'subscription']);

        $donor = $donation->donor;

        if ($donor === null || ! filled($donor->email) || ! $donor->canReceiveEmails()) {
            return;
        }

        if (DonorEmailLog::query()
            ->where('donation_id', $donation->getKey())
            ->where('mailable_class', DonorRefundNotification::class)
            ->exists()) {
            return;
        }

        $org = $donation->campaign?->organization;
        $amountDisplay = $donation->display_payment_amount;
        $messageId = Str::uuid()->toString();
        $mailable = new DonorRefundNotification($donation, $amountDisplay, $messageId);

        app(LogDonorEmail::class)->handle(
            donor: $donor,
            mailable: $mailable,
            organization: $org,
            donation: $donation,
            subscription: $donation->subscription,
            messageId: $messageId,
        );

        Mail::to($donor->email)->queue($mailable);
    }
}
