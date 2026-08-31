<?php

namespace App\Services;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\ProcessingFee;
use App\Support\ReportingPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RevenueReportService
{
    /**
     * @return array<int, array{
     *     id: int,
     *     public_id: string|null,
     *     name: string,
     *     donations: int,
     *     volume_raw: float,
     *     volume: string,
     *     stripe_fees_raw: float,
     *     stripe_fees: string,
     *     fees_raw: float,
     *     fees: string,
     *     avg_donation_raw: float,
     *     avg_donation: string,
     *     effective_rate_raw: float,
     *     effective_rate: string,
     * }>
     */
    public function organizationRows(string $period): array
    {
        [$from, $to] = $this->queryRange($period);

        $succeededDonations = Donation::query()
            ->selectRaw('campaigns.organization_id, COUNT(*) as donation_count, SUM(base_amount) as volume, AVG(base_amount) as avg_donation, SUM(stripe_fee) as stripe_fees')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('donations.status', DonationStatus::Succeeded)
            ->when($from, fn (Builder $q) => $q->where('donations.created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('donations.created_at', '<=', $to))
            ->groupBy('campaigns.organization_id')
            ->get()
            ->keyBy('organization_id');

        $feesByOrg = ProcessingFee::query()
            ->selectRaw('organization_id, SUM(fee_amount) as total_fees')
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to))
            ->groupBy('organization_id')
            ->get()
            ->keyBy('organization_id');

        $organizationIds = $succeededDonations
            ->keys()
            ->merge($feesByOrg->keys())
            ->unique()
            ->values()
            ->all();

        if ($organizationIds === []) {
            return [];
        }

        return Organization::query()
            ->whereIn('id', $organizationIds)
            ->get()
            ->map(function (Organization $org) use ($succeededDonations, $feesByOrg) {
                $orgDonations = $succeededDonations->get($org->id);
                $orgFees = $feesByOrg->get($org->id);
                $volume = (float) ($orgDonations?->volume ?? 0);
                $fees = (float) ($orgFees?->total_fees ?? 0);
                $stripeFees = (float) ($orgDonations?->stripe_fees ?? 0);
                $donations = (int) ($orgDonations?->donation_count ?? 0);
                $avg = $donations > 0 ? $volume / $donations : 0;
                $rate = $volume > 0 ? ($fees / $volume) * 100 : 0;

                return [
                    'id' => $org->id,
                    'public_id' => $org->public_id,
                    'name' => $org->name,
                    'donations' => $donations,
                    'volume_raw' => $volume,
                    'volume' => 'MYR '.number_format($volume, 2, '.', ''),
                    'stripe_fees_raw' => $stripeFees,
                    'stripe_fees' => 'MYR '.number_format($stripeFees, 2, '.', ''),
                    'fees_raw' => $fees,
                    'fees' => 'MYR '.number_format($fees, 2, '.', ''),
                    'avg_donation_raw' => $avg,
                    'avg_donation' => 'MYR '.number_format($avg, 2, '.', ''),
                    'effective_rate_raw' => $rate,
                    'effective_rate' => number_format($rate, 2, '.', '').'%',
                ];
            })
            ->filter(fn (array $row) => $row['donations'] > 0)
            ->sortByDesc('donations')
            ->values()
            ->all();
    }

    public function organizationRowFor(Organization $organization, string $period): ?array
    {
        [$from, $to] = $this->queryRange($period);

        $orgDonations = Donation::query()
            ->selectRaw('campaigns.organization_id, COUNT(*) as donation_count, SUM(base_amount) as volume, AVG(base_amount) as avg_donation, SUM(stripe_fee) as stripe_fees')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('campaigns.organization_id', $organization->id)
            ->where('donations.status', DonationStatus::Succeeded)
            ->when($from, fn (Builder $q) => $q->where('donations.created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('donations.created_at', '<=', $to))
            ->groupBy('campaigns.organization_id')
            ->first();

        if ($orgDonations === null) {
            return null;
        }

        $fees = (float) ProcessingFee::query()
            ->where('organization_id', $organization->id)
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to))
            ->sum('fee_amount');

        $volume = (float) ($orgDonations?->volume ?? 0);
        $donations = (int) ($orgDonations?->donation_count ?? 0);
        $stripeFees = (float) ($orgDonations?->stripe_fees ?? 0);
        $avg = $donations > 0 ? $volume / $donations : 0;
        $rate = $volume > 0 ? ($fees / $volume) * 100 : 0;

        return [
            'id' => $organization->id,
            'public_id' => $organization->public_id,
            'name' => $organization->name,
            'donations' => $donations,
            'volume_raw' => $volume,
            'volume' => 'MYR '.number_format($volume, 2, '.', ''),
            'stripe_fees_raw' => $stripeFees,
            'stripe_fees' => 'MYR '.number_format($stripeFees, 2, '.', ''),
            'fees_raw' => $fees,
            'fees' => 'MYR '.number_format($fees, 2, '.', ''),
            'avg_donation_raw' => $avg,
            'avg_donation' => 'MYR '.number_format($avg, 2, '.', ''),
            'effective_rate_raw' => $rate,
            'effective_rate' => number_format($rate, 2, '.', '').'%',
        ];
    }

    public function rowForOrganization(Organization $organization, string $period): ?array
    {
        return $this->organizationRowFor($organization, $period);
    }

    /**
     * Aggregate revenue report across all organizations.
     *
     * @return array{
     *     summary: array{
     *         totalTransactions: int,
     *         totalDonationVolume: float,
     *         averageDonationSize: float,
     *         totalProcessingFees: float,
     *         averageFeePerTransaction: float,
     *         effectiveFeeRate: float,
     *     },
     *     rows: array<int, array{
     *         id: int,
     *         public_id: string|null,
     *         name: string,
     *         donations: int,
     *         volume_raw: float,
     *         volume: string,
     *         stripe_fees_raw: float,
     *         stripe_fees: string,
     *         fees_raw: float,
     *         fees: string,
     *         avg_donation_raw: float,
     *         avg_donation: string,
     *         effective_rate_raw: float,
     *         effective_rate: string,
     *     }>,
     *     totals: array{
     *         donations: int,
     *         volume_raw: float,
     *         stripe_fees_raw: float,
     *         fees_raw: float,
     *         avg_donation_raw: float,
     *         effective_rate_raw: float,
     *     },
     * }
     */
    public function aggregateReport(string $period): array
    {
        [$from, $to] = $this->queryRange($period);

        $succeeded = Donation::query()
            ->where('donations.status', DonationStatus::Succeeded)
            ->when($from, fn (Builder $q) => $q->where('donations.created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('donations.created_at', '<=', $to));

        $totalDonationVolume = (float) (clone $succeeded)->sum('base_amount');
        $totalTransactions = (int) (clone $succeeded)->count();

        $totalProcessingFees = (float) ProcessingFee::query()
            ->when($from, fn (Builder $q) => $q->where('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->where('created_at', '<=', $to))
            ->sum('fee_amount');

        $summary = [
            'totalTransactions' => $totalTransactions,
            'totalDonationVolume' => $totalDonationVolume,
            'averageDonationSize' => $totalTransactions > 0 ? $totalDonationVolume / $totalTransactions : 0,
            'totalProcessingFees' => $totalProcessingFees,
            'averageFeePerTransaction' => $totalTransactions > 0 ? $totalProcessingFees / $totalTransactions : 0,
            'effectiveFeeRate' => $totalDonationVolume > 0 ? ($totalProcessingFees / $totalDonationVolume) * 100 : 0,
        ];

        $rows = $this->organizationRows($period);

        $totals = [
            'donations' => (int) array_sum(array_column($rows, 'donations')),
            'volume_raw' => (float) array_sum(array_column($rows, 'volume_raw')),
            'stripe_fees_raw' => (float) array_sum(array_column($rows, 'stripe_fees_raw')),
            'fees_raw' => (float) array_sum(array_column($rows, 'fees_raw')),
        ];
        $totals['avg_donation_raw'] = $totals['donations'] > 0 ? $totals['volume_raw'] / $totals['donations'] : 0;
        $totals['effective_rate_raw'] = $totals['volume_raw'] > 0 ? ($totals['fees_raw'] / $totals['volume_raw']) * 100 : 0;

        return compact('summary', 'rows', 'totals');
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    /**
     * The period as UTC instants, for comparing against stored timestamps.
     * dateRange() returns the local calendar dates, which are what the labels
     * and exports quote.
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public function queryRange(string $period): array
    {
        [$from, $to] = $this->dateRange($period);

        if ($from === null || $to === null) {
            return [null, null];
        }

        return ReportingPeriod::toUtc([
            ReportingPeriod::parseLocalDate($from)->startOfDay(),
            ReportingPeriod::parseLocalDate($to)->endOfDay(),
        ]);
    }

    public function dateRange(string $period): array
    {
        return match ($period) {
            'today' => [ReportingPeriod::localNow()->toDateString(), ReportingPeriod::localNow()->toDateString()],
            'yesterday' => [ReportingPeriod::localNow()->subDay()->toDateString(), ReportingPeriod::localNow()->subDay()->toDateString()],
            'last_7_days' => [ReportingPeriod::localNow()->subDays(6)->toDateString(), ReportingPeriod::localNow()->toDateString()],
            'last_30_days' => [ReportingPeriod::localNow()->subDays(29)->toDateString(), ReportingPeriod::localNow()->toDateString()],
            'last_90_days' => [ReportingPeriod::localNow()->subDays(89)->toDateString(), ReportingPeriod::localNow()->toDateString()],
            'last_week' => [ReportingPeriod::localNow()->subWeek()->startOfWeek()->toDateString(), ReportingPeriod::localNow()->subWeek()->endOfWeek()->toDateString()],
            'last_month' => [ReportingPeriod::localNow()->subMonth()->startOfMonth()->toDateString(), ReportingPeriod::localNow()->subMonth()->endOfMonth()->toDateString()],
            'last_6_months' => [ReportingPeriod::localNow()->subMonths(6)->startOfMonth()->toDateString(), ReportingPeriod::localNow()->subMonth()->endOfMonth()->toDateString()],
            'last_year' => [ReportingPeriod::localNow()->subYear()->startOfYear()->toDateString(), ReportingPeriod::localNow()->subYear()->endOfYear()->toDateString()],
            'this_week' => [ReportingPeriod::localNow()->startOfWeek()->toDateString(), ReportingPeriod::localNow()->endOfWeek()->toDateString()],
            'this_month' => [ReportingPeriod::localNow()->startOfMonth()->toDateString(), ReportingPeriod::localNow()->endOfMonth()->toDateString()],
            'this_year' => [ReportingPeriod::localNow()->startOfYear()->toDateString(), ReportingPeriod::localNow()->endOfYear()->toDateString()],
            default => [null, null],
        };
    }

    public function periodLabel(string $period): string
    {
        return match ($period) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'last_90_days' => 'Last 90 Days',
            'last_week' => 'Last Week',
            'last_month' => 'Last Month',
            'last_6_months' => 'Last 6 Months',
            'last_year' => 'Last Year',
            'this_week' => 'This Week',
            'this_month' => 'This Month',
            'this_year' => 'This Year',
            default => 'All Time',
        };
    }

    public function periodDateRangeLabel(string $period): string
    {
        [$from, $to] = $this->dateRange($period);

        if ($from === null) {
            return 'All Time';
        }

        return $from === $to ? $from : "$from to $to";
    }
}
