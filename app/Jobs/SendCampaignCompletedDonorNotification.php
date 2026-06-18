<?php

namespace App\Jobs;

use App\Actions\DonorEmailLog\LogDonorEmail;
use App\Enums\DonationStatus;
use App\Mail\CampaignCompletedDonorNotification;
use App\Models\Campaign;
use App\Models\Donor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendCampaignCompletedDonorNotification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Campaign $campaign,
    ) {}

    public function handle(): void
    {
        $this->campaign->loadMissing('organization');

        $org = $this->campaign->organization;

        if ($org === null) {
            return;
        }

        $settings = $org->settings ?? [];

        if (! ($settings['notify_campaign_milestone'] ?? false)) {
            return;
        }

        $donorIds = $this->campaign->donations()
            ->where('status', DonationStatus::Succeeded)
            ->distinct()
            ->pluck('donor_id');

        if ($donorIds->isEmpty()) {
            return;
        }

        Donor::query()->whereIn('id', $donorIds)->each(function (Donor $donor) {
            $mailable = new CampaignCompletedDonorNotification(
                campaign: $this->campaign,
                donor: $donor,
            );

            Mail::to($donor->email)->queue($mailable);

            app(LogDonorEmail::class)->handle(
                donor: $donor,
                mailable: $mailable,
                organization: $this->campaign->organization,
                metadata: [
                    'campaign_id' => $this->campaign->getKey(),
                ],
            );
        });
    }
}
