<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\PaymentMethodChangedNotification;
use App\Models\DonorPaymentMethod;
use App\Models\Subscription;
use App\Models\User;
use App\Support\MailtrapThrottle;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPaymentMethodChangedNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public ?DonorPaymentMethod $previousPaymentMethod,
        public DonorPaymentMethod $newPaymentMethod,
    ) {}

    public function handle(): void
    {
        $this->subscription->loadMissing(['donor', 'campaign.organization']);

        $org = $this->subscription->campaign?->organization;

        if ($org === null) {
            return;
        }

        $settings = $org->settings ?? [];

        if (! ($settings['notify_payment_method_changed'] ?? true)) {
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
                    new PaymentMethodChangedNotification($this->subscription, $this->previousPaymentMethod, $this->newPaymentMethod)
                );
        }
    }
}
