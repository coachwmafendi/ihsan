<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\FailedPaymentNotification;
use App\Models\Subscription;
use App\Models\User;
use App\Support\MailtrapThrottle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendFailedPaymentNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public ?string $failureMessage = null,
        public bool $isFinalAttempt = false,
    ) {}

    public function handle(): void
    {
        $this->subscription->loadMissing(['donor', 'campaign.organization']);

        $org = $this->subscription->campaign?->organization;

        if ($org === null) {
            return;
        }

        $settings = $org->settings ?? [];

        if (! ($settings['failed_payment_notification'] ?? true)) {
            return;
        }

        $admins = User::query()
            ->where('organization_id', $org->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        $delay = MailtrapThrottle::delaySeconds();

        foreach ($admins as $index => $admin) {
            Mail::to($admin->email)
                ->later(
                    now()->addSeconds($index * $delay),
                    new FailedPaymentNotification($this->subscription, $this->failureMessage, $this->isFinalAttempt)
                );
        }
    }
}
