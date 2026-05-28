<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\NewDonationNotification;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendNewDonationNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Donation $donation,
    ) {}

    public function handle(): void
    {
        $this->donation->refresh();
        $this->donation->loadMissing(['donor', 'campaign.organization']);

        $org = $this->donation->campaign?->organization;

        if ($org === null) {
            return;
        }

        $settings = $org->settings ?? [];

        if (! ($settings['notify_new_donation'] ?? true)) {
            return;
        }

        $largeDonationEnabled = $settings['notify_large_donation'] ?? false;
        $largeThreshold = (int) ($settings['large_donation_threshold'] ?? 1000);

        $amount = (float) ($this->donation->base_amount ?? $this->donation->gross_amount);

        if ($largeDonationEnabled && $amount >= $largeThreshold) {
            return;
        }

        $admins = User::query()
            ->where('organization_id', $org->getKey())
            ->where('role', UserRole::NgoAdmin)
            ->get();

        foreach ($admins as $admin) {
            Mail::to($admin->email)
                ->queue(new NewDonationNotification($this->donation));
        }
    }
}
