<?php

namespace App\Console\Commands;

use App\Enums\DonationType;
use App\Enums\UserRole;
use App\Mail\MonthlyReport;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use App\Services\DonationReportFigures;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class SendMonthlyReport extends Command
{
    protected $signature = 'ihsan:send-monthly-report {--period= : The period to report for (Y-m-d format, defaults to previous month)}';

    protected $description = 'Send monthly donation report to organizations that have it enabled';

    private DonationReportFigures $figures;

    public function handle(DonationReportFigures $figures): void
    {
        $this->figures = $figures;

        $period = $this->option('period')
            ? now('Asia/Kuala_Lumpur')->parse($this->option('period'))
            : now('Asia/Kuala_Lumpur')->subMonthNoOverflow();

        $periodLabel = $period->format('F Y');
        $startUtc = $period->copy()->startOfMonth()->utc();
        $endUtc = $period->copy()->endOfMonth()->utc();
        $previousStartUtc = $period->copy()->subMonthNoOverflow()->startOfMonth()->utc();
        $previousEndUtc = $period->copy()->subMonthNoOverflow()->endOfMonth()->utc();

        $organizations = Organization::query()
            ->where('settings->monthly_report', true)
            ->whereNotNull('settings')
            ->whereNull('deleted_at')
            ->get();

        foreach ($organizations as $org) {
            $lastSent = $org->settings['monthly_report_last_sent'] ?? null;

            if ($lastSent === $period->toDateString()) {
                continue;
            }

            $admins = User::query()
                ->where('organization_id', $org->getKey())
                ->where('role', UserRole::NgoAdmin)
                ->get();

            // Leave the month unmarked so the report still goes out once an
            // admin exists, instead of being recorded as sent to nobody.
            if ($admins->isEmpty()) {
                continue;
            }

            $donations = $this->figures->succeededDonations($org)
                ->with('campaign')
                ->whereBetween('created_at', [$startUtc, $endUtc])
                ->get();

            $campaigns = $donations->groupBy('campaign_id')->map(fn (Collection $items): array => [
                'title' => $items->first()->campaign->title,
                'count' => $items->count(),
                'total' => number_format($items->sum('report_amount'), 2),
                'approximate' => $this->figures->hasForeignCurrency($items),
            ])->values()->toArray();

            $total = (float) $donations->sum('report_amount');
            $previousTotal = $this->figures->totalBetween($org, $previousStartUtc, $previousEndUtc);

            foreach ($admins as $admin) {
                Mail::to($admin->email)->queue(
                    new MonthlyReport(
                        organization: $org,
                        donationCount: $donations->count(),
                        totalAmount: number_format($total, 2),
                        campaigns: $campaigns,
                        period: $periodLabel,
                        hasApproximation: $this->figures->hasForeignCurrency($donations),
                        previousTotalAmount: number_format($previousTotal, 2),
                        changePercent: $this->figures->changePercent($total, $previousTotal),
                        highlights: $this->highlights($org, $donations, $startUtc, $endUtc),
                        reportUrl: appPanelRoute('app.reports.monthly-donations', ['month' => $period->format('Y-m')]),
                    )
                );
            }

            $settings = $org->settings;
            $settings['monthly_report_last_sent'] = $period->toDateString();
            $org->update(['settings' => $settings]);
        }
    }

    /**
     * @param  Collection<int, Donation>  $donations
     * @return array{one_time_count: int, one_time_total: string, recurring_count: int, recurring_total: string, new_donor_count: int, returning_donor_count: int, average_amount: string, largest_amount: string, new_plan_count: int, cancelled_plan_count: int, past_due_plan_count: int}
     */
    private function highlights(Organization $organization, Collection $donations, CarbonInterface $startUtc, CarbonInterface $endUtc): array
    {
        [$recurring, $oneTime] = $donations->partition(
            fn (Donation $donation): bool => $donation->type === DonationType::Recurring || $donation->subscription_id !== null
        );

        $donorIds = $donations->pluck('donor_id')->unique();
        $returningDonorCount = $donorIds->isEmpty() ? 0 : $this->figures->succeededDonations($organization)
            ->whereIn('donor_id', $donorIds)
            ->where('created_at', '<', $startUtc)
            ->distinct()
            ->count('donor_id');

        return [
            'one_time_count' => $oneTime->count(),
            'one_time_total' => number_format($oneTime->sum('report_amount'), 2),
            'recurring_count' => $recurring->count(),
            'recurring_total' => number_format($recurring->sum('report_amount'), 2),
            'new_donor_count' => $donorIds->count() - $returningDonorCount,
            'returning_donor_count' => $returningDonorCount,
            'average_amount' => number_format($donations->isEmpty() ? 0 : $donations->avg('report_amount'), 2),
            'largest_amount' => number_format($donations->max('report_amount') ?? 0, 2),
            ...$this->figures->planCounts($organization, $startUtc, $endUtc),
        ];
    }
}
