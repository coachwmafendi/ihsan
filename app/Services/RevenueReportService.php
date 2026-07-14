<?php

namespace App\Services;

use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Organization;
use App\Models\ProcessingFee;
use Illuminate\Database\Eloquent\Builder;

class RevenueReportService
{
    /**
     * @return array<int, array{
     *     id: int,
     *     public_id: string,
     *     name: string,
     *     donations: int,
     *     volume_raw: float,
     *     volume: string,
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
        [$from, $to] = $this->dateRange($period);

        $succeededDonations = Donation::query()
            ->selectRaw('campaigns.organization_id, COUNT(*) as donation_count, SUM(base_amount) as volume, AVG(base_amount) as avg_donation')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->where('donations.status', DonationStatus::Succeeded)
            ->when($from, fn (Builder $q) => $q->whereDate('donations.created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('donations.created_at', '<=', $to))
            ->groupBy('campaigns.organization_id')
            ->get()
            ->keyBy('organization_id');

        $feesByOrg = ProcessingFee::query()
            ->selectRaw('organization_id, SUM(fee_amount) as total_fees')
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to))
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

    public function rowForOrganization(Organization $organization, string $period): ?array
    {
        foreach ($this->organizationRows($period) as $row) {
            if ($row['id'] === $organization->id) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    public function dateRange(string $period): array
    {
        return match ($period) {
            'today' => [today()->toDateString(), today()->toDateString()],
            'yesterday' => [today()->subDay()->toDateString(), today()->subDay()->toDateString()],
            'last_7_days' => [today()->subDays(6)->toDateString(), today()->toDateString()],
            'last_30_days' => [today()->subDays(29)->toDateString(), today()->toDateString()],
            'last_90_days' => [today()->subDays(89)->toDateString(), today()->toDateString()],
            'last_week' => [today()->subWeek()->startOfWeek()->toDateString(), today()->subWeek()->endOfWeek()->toDateString()],
            'last_month' => [today()->subMonth()->startOfMonth()->toDateString(), today()->subMonth()->endOfMonth()->toDateString()],
            'last_6_months' => [today()->subMonths(6)->startOfMonth()->toDateString(), today()->subMonth()->endOfMonth()->toDateString()],
            'last_year' => [today()->subYear()->startOfYear()->toDateString(), today()->subYear()->endOfYear()->toDateString()],
            'this_week' => [today()->startOfWeek()->toDateString(), today()->endOfWeek()->toDateString()],
            'this_month' => [today()->startOfMonth()->toDateString(), today()->endOfMonth()->toDateString()],
            'this_year' => [today()->startOfYear()->toDateString(), today()->endOfYear()->toDateString()],
            default => [null, null],
        };
    }

    public function periodLabel(string $period): string
    {
        return match ($period) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 days',
            'last_30_days' => 'Last 30 days',
            'last_90_days' => 'Last 90 days',
            'last_week' => 'Last week',
            'last_month' => 'Last month',
            'last_6_months' => 'Last 6 months',
            'last_year' => 'Last year',
            'this_week' => 'This week',
            'this_month' => 'This month',
            'this_year' => 'This year',
            default => 'All time',
        };
    }

    public function periodDateRangeLabel(string $period): string
    {
        [$from, $to] = $this->dateRange($period);

        if ($from === null) {
            return 'All time';
        }

        return $from === $to ? $from : "$from to $to";
    }
}
