<?php

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Enums\UserRole;
use App\Mail\WeeklyDonationSummary;
use App\Models\Campaign;
use App\Models\Organization;
use App\Models\User;
use App\Services\DonationReportFigures;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;

class SendWeeklyDonationSummary extends Command
{
    protected $signature = 'ihsan:send-weekly-summary';

    protected $description = 'Send weekly donation summary to organizations that have it enabled';

    public function handle(DonationReportFigures $figures): void
    {
        $reportNow = now('Asia/Kuala_Lumpur');
        $startOfWeek = $reportNow->copy()->subWeek()->startOfWeek();
        $endOfWeek = $reportNow->copy()->subWeek()->endOfWeek();
        $startUtc = $startOfWeek->copy()->utc();
        $endUtc = $endOfWeek->copy()->utc();
        $previousStartUtc = $startOfWeek->copy()->subWeek()->utc();
        $previousEndUtc = $endOfWeek->copy()->subWeek()->utc();
        $periodLabel = myrTime($startOfWeek, withLabel: false, format: 'd M').' – '.myrTime($endOfWeek, withLabel: false, format: 'd M Y');

        $organizations = Organization::query()
            ->where('settings->weekly_report', true)
            ->whereNotNull('settings')
            ->whereNull('deleted_at')
            ->get();

        foreach ($organizations as $org) {
            if (($org->settings['weekly_report_last_sent'] ?? null) === $startOfWeek->toDateString()) {
                continue;
            }

            $donations = $figures->succeededDonations($org)
                ->whereBetween('created_at', [$startUtc, $endUtc])
                ->get();

            // Active campaigns show even when quiet; a campaign that has since
            // ended still shows when it raised money, so the rows add up to the total.
            $reportedCampaigns = Campaign::query()
                ->where('organization_id', $org->getKey())
                ->where(fn (Builder $query) => $query
                    ->where('status', CampaignStatus::Active)
                    ->orWhereIn('id', $donations->pluck('campaign_id')->unique()))
                ->orderBy('id')
                ->get();

            if ($reportedCampaigns->isEmpty()) {
                continue;
            }

            $admins = User::query()
                ->where('organization_id', $org->getKey())
                ->where('role', UserRole::NgoAdmin)
                ->get();

            // Leave the week unmarked so it is not recorded as sent to nobody.
            if ($admins->isEmpty()) {
                continue;
            }

            $donationsByCampaign = $donations->groupBy('campaign_id');

            $campaigns = $reportedCampaigns->map(function (Campaign $campaign) use ($donationsByCampaign, $figures): array {
                $items = $donationsByCampaign->get($campaign->getKey(), collect());

                return [
                    'title' => $campaign->title,
                    'count' => $items->count(),
                    'total' => number_format($items->sum('report_amount'), 2),
                    'approximate' => $figures->hasForeignCurrency($items),
                ];
            })->values()->toArray();

            $total = (float) $donations->sum('report_amount');
            $previousTotal = $figures->totalBetween($org, $previousStartUtc, $previousEndUtc);

            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(
                    new WeeklyDonationSummary(
                        organization: $org,
                        donationCount: $donations->count(),
                        totalAmount: number_format($total, 2),
                        campaigns: $campaigns,
                        period: $periodLabel,
                        hasApproximation: $figures->hasForeignCurrency($donations),
                        previousTotalAmount: number_format($previousTotal, 2),
                        changePercent: $figures->changePercent($total, $previousTotal),
                        plans: $figures->planCounts($org, $startUtc, $endUtc),
                        reportUrl: appPanelRoute('app.reports.monthly-donations', [
                            'from' => $startOfWeek->toDateString(),
                            'to' => $endOfWeek->toDateString(),
                        ]),
                    )
                );
            }

            $settings = $org->settings;
            $settings['weekly_report_last_sent'] = $startOfWeek->toDateString();
            $org->update(['settings' => $settings]);
        }
    }
}
