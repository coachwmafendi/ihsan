<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Mail\DonorDunningNotification;
use App\Models\Subscription;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendDonorDunningNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public int $retryCount,
        public bool $isFinalAttempt = false,
    ) {}

    public function handle(): void
    {
        $this->subscription->loadMissing(['donor', 'campaign.organization']);

        $donor = $this->subscription->donor;

        if ($donor === null || ! filled($donor->email) || ! $donor->canReceiveEmails()) {
            return;
        }

        $org = $this->subscription->campaign?->organization;
        $messageId = Str::uuid()->toString();
        $mailable = new DonorDunningNotification(
            $this->subscription,
            $this->retryCount,
            $this->isFinalAttempt,
            $messageId,
        );

        app(LogDonorEmail::class)->handle(
            donor: $donor,
            mailable: $mailable,
            organization: $org,
            subscription: $this->subscription,
            metadata: [
                'retry_count' => $this->retryCount,
                'is_final_attempt' => $this->isFinalAttempt,
            ],
            messageId: $messageId,
        );

        Mail::to($donor->email)->queue($mailable);
    }
}
