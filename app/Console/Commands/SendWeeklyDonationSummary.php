<?php

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\UserRole;
use App\Mail\WeeklyDonationSummary;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDonationSummary extends Command
{
    protected $signature = 'ihsan:send-weekly-summary';

    protected $description = 'Send weekly donation summary to organizations that have it enabled';

    public function handle(): void
    {
        $startOfWeek = now()->subWeek()->startOfWeek();
        $endOfWeek = now()->subWeek()->endOfWeek();
        $periodLabel = $startOfWeek->format('d M').' – '.$endOfWeek->format('d M Y');

        $organizations = Organization::query()
            ->where('settings->weekly_report', true)
            ->whereNotNull('settings')
            ->get();

        foreach ($organizations as $org) {
            $donations = Donation::query()
                ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->getKey()))
                ->where('status', DonationStatus::Succeeded)
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->get();

            $activeCampaigns = Campaign::query()
                ->where('organization_id', $org->getKey())
                ->where('status', CampaignStatus::Active)
                ->get();

            if ($activeCampaigns->isEmpty() && $donations->isEmpty()) {
                continue;
            }

            $donationsByCampaign = $donations->groupBy('campaign_id');

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

            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(
                    new WeeklyDonationSummary(
                        organization: $org,
                        donationCount: $donations->count(),
                        totalAmount: number_format($donations->sum('gross_amount'), 2),
                        campaigns: $campaigns,
                        period: $periodLabel,
                    )
                );
            }
        }
    }
}
