<?php

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\UserRole;
use App\Mail\DailyDonationSummary;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendDailyDonationSummary extends Command
{
    protected $signature = 'ihsan:send-daily-summary';

    protected $description = 'Send daily donation summary to organizations at their configured time';

    public function handle(): void
    {
        $organizations = Organization::query()
            ->where('settings->daily_donation_summary', true)
            ->whereNotNull('settings')
            ->get();

        foreach ($organizations as $org) {
            $donations = Donation::query()
                ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->getKey()))
                ->where('status', DonationStatus::Succeeded)
                ->whereDate('created_at', today()->subDay())
                ->get();

            $donationsByCampaign = $donations->groupBy('campaign_id');

            $activeCampaigns = Campaign::query()
                ->where('organization_id', $org->getKey())
                ->where('status', CampaignStatus::Active)
                ->get();

            if ($activeCampaigns->isEmpty() && $donations->isEmpty()) {
                continue;
            }

            $campaigns = $activeCampaigns->map(function ($campaign) use ($donationsByCampaign) {
                $items = $donationsByCampaign->get($campaign->getKey(), collect());

                return [
                    'title' => $campaign->title,
                    'count' => $items->count(),
                    'total' => number_format($items->sum('gross_amount'), 2),
                ];
            })->values()->toArray();

            $admins = User::query()
                ->where('organization_id', $org->getKey())
                ->where('role', UserRole::NgoAdmin)
                ->get();

            $oneTime = $donations->where('type', DonationType::OneTime);
            $recurring = $donations->where('type', DonationType::Recurring);

            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(
                    new DailyDonationSummary(
                        organization: $org,
                        donationCount: $donations->count(),
                        totalAmount: number_format($donations->sum('gross_amount'), 2),
                        oneTimeCount: $oneTime->count(),
                        oneTimeTotal: number_format($oneTime->sum('gross_amount'), 2),
                        recurringCount: $recurring->count(),
                        recurringTotal: number_format($recurring->sum('gross_amount'), 2),
                        campaigns: $campaigns,
                    )
                );
            }
        }
    }
}
