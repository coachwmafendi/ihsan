<?php

namespace App\Console\Commands;

use App\Enums\DonationStatus;
use App\Enums\UserRole;
use App\Mail\MonthlyReport;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendMonthlyReport extends Command
{
    protected $signature = 'ihsan:send-monthly-report {--period= : The period to report for (Y-m-d format, defaults to previous month)}';

    protected $description = 'Send monthly donation report to organizations that have it enabled';

    public function handle(): void
    {
        $period = $this->option('period')
            ? now()->parse($this->option('period'))
            : now()->subMonthNoOverflow();

        $periodLabel = $period->format('F Y');
        $startOfMonth = $period->copy()->startOfMonth();
        $endOfMonth = $period->copy()->endOfMonth();

        $organizations = Organization::query()
            ->where('settings->monthly_report', true)
            ->whereNotNull('settings')
            ->get();

        foreach ($organizations as $org) {
            $lastSent = $org->settings['monthly_report_last_sent'] ?? null;

            if ($lastSent === $period->toDateString()) {
                continue;
            }

            $donations = Donation::query()
                ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->getKey()))
                ->where('status', DonationStatus::Succeeded)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->get();

            $campaigns = $donations->groupBy('campaign_id')->map(function ($items) {
                $campaign = $items->first()->campaign;

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
                    new MonthlyReport(
                        organization: $org,
                        donationCount: $donations->count(),
                        totalAmount: number_format($donations->sum('gross_amount'), 2),
                        campaigns: $campaigns,
                        period: $periodLabel,
                    )
                );
            }

            $settings = $org->settings;
            $settings['monthly_report_last_sent'] = $period->toDateString();
            $org->update(['settings' => $settings]);
        }
    }
}
