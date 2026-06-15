<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Enums\DonationStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Insights extends Component
{
    public string $period = '30_days';

    #[Computed]
    public function dateRange(): array
    {
        return match ($this->period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            '90_days' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            default => [null, null],
        };
    }

    #[Computed]
    public function stats(): array
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;

        $donationsQuery = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        return [
            'total_amount' => $donationsQuery->sum('gross_amount'),
            'total_count' => $donationsQuery->count(),
            'active_campaigns' => Campaign::where('organization_id', $org->id)->where('status', 'active')->count(),
            'total_donors' => Donor::whereHas('donations.campaign', fn ($q) => $q->where('organization_id', $org->id))->count(),
            'active_subscriptions' => Subscription::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))->where('status', 'active')->count(),
        ];
    }

    #[Computed]
    public function donationTrend(): array
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;
        $days = match ($this->period) {
            'today', 'yesterday' => 1,
            '7_days' => 7,
            '30_days' => 30,
            '90_days' => 90,
            'this_month' => now()->daysInMonth,
            default => 30,
        };

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $amount = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
                ->where('status', DonationStatus::Succeeded)
                ->whereDate('created_at', $date)
                ->sum('gross_amount');
            $data[] = [
                'date' => $date->format('j M'),
                'amount' => (float) $amount,
            ];
        }

        return $data;
    }

    #[Computed]
    public function campaignsBreakdown(): array
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;

        return Campaign::where('organization_id', $org->id)
            ->withSum(['donations' => fn ($q) => $q->where('status', DonationStatus::Succeeded)->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))], 'gross_amount')
            ->orderByDesc('donations_sum_gross_amount')
            ->limit(5)
            ->get()
            ->filter(fn ($c) => ($c->donations_sum_gross_amount ?? 0) > 0)
            ->map(fn ($c) => ['name' => $c->title, 'amount' => (float) ($c->donations_sum_gross_amount ?? 0)])
            ->values()
            ->toArray();
    }

    #[Computed]
    public function donationSizes(): array
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;

        $baseQuery = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to));

        $under50 = (clone $baseQuery)->where('gross_amount', '<', 50)->count();
        $fiftyTo100 = (clone $baseQuery)->whereBetween('gross_amount', [50, 100])->count();
        $hundredTo500 = (clone $baseQuery)->whereBetween('gross_amount', [100.01, 500])->count();
        $over500 = (clone $baseQuery)->where('gross_amount', '>', 500)->count();

        $total = $under50 + $fiftyTo100 + $hundredTo500 + $over500;

        return [
            ['label' => 'Under RM 50', 'count' => $under50, 'percentage' => $total > 0 ? round(($under50 / $total) * 100) : 0],
            ['label' => 'RM 50 – 100', 'count' => $fiftyTo100, 'percentage' => $total > 0 ? round(($fiftyTo100 / $total) * 100) : 0],
            ['label' => 'RM 100 – 500', 'count' => $hundredTo500, 'percentage' => $total > 0 ? round(($hundredTo500 / $total) * 100) : 0],
            ['label' => 'Over RM 500', 'count' => $over500, 'percentage' => $total > 0 ? round(($over500 / $total) * 100) : 0],
        ];
    }

    #[Computed]
    public function paymentMethods(): array
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return [];
        }

        [$from, $to] = $this->dateRange;

        $methods = Donation::whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->where('status', DonationStatus::Succeeded)
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->selectRaw('payment_method_type, COUNT(*) as count')
            ->groupBy('payment_method_type')
            ->orderByDesc('count')
            ->get();

        $total = $methods->sum('count');

        return $methods->map(fn ($m) => [
            'name' => match ($m->payment_method_type) {
                'card' => 'Card',
                'bank_transfer' => 'Bank Transfer',
                'fpx' => 'FPX',
                'grabpay' => 'GrabPay',
                'grabpay_paylater' => 'GrabPay PayLater',
                'boost' => 'Boost',
                'tng' => 'Touch n Go',
                'alipay' => 'Alipay',
                'wechatpay' => 'WeChat Pay',
                default => ucfirst($m->payment_method_type ?? 'Other'),
            },
            'count' => (int) $m->count,
            'percentage' => $total > 0 ? round(((int) $m->count / $total) * 100) : 0,
        ])->toArray();
    }

    #[Computed]
    public function recentDonations()
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return collect();
        }

        [$from, $to] = $this->dateRange;

        return Donation::with(['campaign', 'donor'])
            ->whereHas('campaign', fn ($q) => $q->where('organization_id', $org->id))
            ->when($from, fn ($q) => $q->whereDate('donations.created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('donations.created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.app.insights', ['title' => 'Insights']);
    }
}
