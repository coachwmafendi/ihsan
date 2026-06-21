<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonorRecurringPaymentNotification;
use App\Models\Donation;
use App\Models\DonorEmailLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendDonorRecurringPaymentNotification implements ShouldQueue
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

        if ($donation->subscription === null) {
            return;
        }

        $donor = $donation->donor;

        if ($donor === null || ! filled($donor->email) || ! $donor->canReceiveEmails()) {
            return;
        }

        if (DonorEmailLog::query()
            ->where('donation_id', $donation->getKey())
            ->where('mailable_class', DonorRecurringPaymentNotification::class)
            ->exists()) {
            return;
        }

        $org = $donation->campaign?->organization;
        $messageId = Str::uuid()->toString();
        $mailable = new DonorRecurringPaymentNotification($donation, $messageId);

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
