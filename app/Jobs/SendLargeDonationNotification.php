<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\LargeDonationNotification;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendLargeDonationNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donation $donation,
    ) {}

    public function handle(): void
    {
        $this->donation->loadMissing(['donor', 'campaign.organization']);

        $org = $this->donation->campaign?->organization;

        if ($org === null) {
            return;
        }

        $settings = $org->settings ?? [];

        if (! ($settings['notify_large_donation'] ?? false)) {
            return;
        }

        $threshold = (int) ($settings['large_donation_threshold'] ?? 1000);

        $amount = (float) ($this->donation->base_amount ?? $this->donation->gross_amount);

        if ($amount < $threshold) {
            return;
        }

        $admins = User::query()
            ->where('organization_id', $org->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)
                ->queue(new LargeDonationNotification($this->donation));
        }
    }
}
