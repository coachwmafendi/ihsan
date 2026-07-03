<?php

declare(strict_types=1);

namespace App\Livewire\App\Subscriptions;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\SubscriptionExportController;
use App\Models\Campaign;
use App\Models\Subscription;
use App\Services\SubscriptionSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SubscriptionIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Url(except: '')]
    public string $campaignFilter = '';

    #[Url(except: 'all_time')]
    public string $period = 'all_time';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showExportModal = false;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCampaignFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPeriod(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function clearDate(): void
    {
        $this->period = 'all_time';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function redirectToShow(string $publicId): void
    {
        $this->redirectRoute('app.subscriptions.show', $publicId, navigate: true);
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    #[Computed]
    public function organization()
    {
        return Auth::user()?->organization;
    }

    #[Computed]
    public function campaigns()
    {
        $org = $this->organization;

        if (! $org) {
            return collect();
        }

        return Campaign::where('organization_id', $org->id)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    /**
     * @return array<int, array{key: string, label: string, default?: bool}>
     */
    #[Computed]
    public function exportFields(): array
    {
        return SubscriptionExportController::$fields;
    }

    #[Computed]
    public function dateChipLabel(): string
    {
        if ($this->period === 'custom' && $this->dateFrom && $this->dateTo) {
            return myrTime(Carbon::parse($this->dateFrom), withLabel: false, format: 'M d').' – '.myrTime(Carbon::parse($this->dateTo), withLabel: false, format: 'M d, Y');
        }

        return match ($this->period) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7_days' => 'Last 7 days',
            '14_days' => 'Last 14 days',
            '30_days' => 'Last 30 days',
            'this_week' => 'This week',
            'this_month' => 'This month',
            'this_year' => 'This year',
            'last_week' => 'Last week',
            'last_month' => 'Last month',
            'last_year' => 'Last year',
            default => 'Date',
        };
    }

    /**
     * @return array{?Carbon, ?Carbon}
     */
    public function periodRange(): array
    {
        if ($this->period === 'custom') {
            return [
                $this->dateFrom ? Carbon::parse($this->dateFrom)->startOfDay() : null,
                $this->dateTo ? Carbon::parse($this->dateTo)->endOfDay() : null,
            ];
        }

        return match ($this->period) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            '7_days' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
            '14_days' => [now()->subDays(13)->startOfDay(), now()->endOfDay()],
            '30_days' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
            '90_days' => [now()->subDays(89)->startOfDay(), now()->endOfDay()],
            'this_week' => [now()->startOfWeek(), now()->endOfWeek()],
            'this_month' => [now()->startOfMonth(), now()->endOfMonth()],
            'this_year' => [now()->startOfYear(), now()->endOfYear()],
            'last_week' => [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()],
            'last_month' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
            'last_year' => [now()->subYear()->startOfYear(), now()->subYear()->endOfYear()],
            default => [null, null],
        };
    }

    private function baseQuery(): Builder
    {
        $org = $this->organization;

        $query = Subscription::query()
            ->when($org, function (Builder $q) use ($org): void {
                $q->whereHas('campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id));
            })
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->with([
                'campaign',
                'donor',
                'donations' => fn ($q) => $q->select('id', 'subscription_id', 'payment_method_brand', 'payment_method_type', 'base_amount', 'exchange_rate', 'currency')->orderByDesc('created_at'),
            ])
            ->withSum('donations', 'base_amount');

        if (filled($this->search)) {
            $search = '%'.strtolower($this->search).'%';
            $query->whereHas('donor', function (Builder $q) use ($search): void {
                $q->whereRaw('LOWER(name) LIKE ?', [$search])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$search]);
            });
        }

        if (filled($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        if (filled($this->campaignFilter)) {
            $query->where('campaign_id', $this->campaignFilter);
        }

        [$start, $end] = $this->periodRange();

        if ($start !== null && $end !== null) {
            $query->whereBetween('subscriptions.created_at', [$start, $end]);
        }

        return $query;
    }

    #[Computed]
    public function subscriptions()
    {
        $query = $this->baseQuery();

        $allowedSorts = ['donor', 'amount', 'interval', 'status', 'current_period_end', 'current_period_start', 'created_at'];
        $field = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'created_at';
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'desc';

        if ($field === 'donor') {
            $query->leftJoin('donors', 'donors.id', '=', 'subscriptions.donor_id')
                ->orderBy('donors.name', $direction)
                ->select('subscriptions.*');
        } else {
            $query->orderBy($field, $direction);
        }

        return $query->paginate(25);
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    #[Computed]
    public function expectedMonthlyTotal(): float
    {
        $org = $this->organization;

        if (! $org) {
            return 0.0;
        }

        $subscriptions = Subscription::query()
            ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', $org->id))
            ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::PastDue])
            ->where('cancel_at_period_end', false)
            ->with([
                'donations' => fn ($q) => $q->select('id', 'subscription_id', 'exchange_rate')->orderByDesc('created_at'),
            ])
            ->get(['id', 'amount', 'currency', 'interval']);

        $total = 0.0;

        foreach ($subscriptions as $subscription) {
            $monthlyFactor = SubscriptionSchedule::monthlyFactor($subscription->interval);

            $latestDonation = $subscription->donations->first();
            $exchangeRate = strtolower($subscription->currency) === 'myr'
                ? 1.0
                : (float) ($latestDonation?->exchange_rate ?? 1.0);

            $total += (float) $subscription->amount * $monthlyFactor * $exchangeRate;
        }

        return round($total, 2);
    }

    public function render()
    {
        return view('livewire.app.subscriptions.index', [
            'title' => 'Recurring Plans',
        ]);
    }
}
