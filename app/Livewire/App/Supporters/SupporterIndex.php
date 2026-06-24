<?php

declare(strict_types=1);

namespace App\Livewire\App\Supporters;

use App\Http\Controllers\SupporterExportController;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class SupporterIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all_time')]
    public string $period = 'all_time';

    #[Url(except: '')]
    public string $dateFrom = '';

    #[Url(except: '')]
    public string $dateTo = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    #[Url(except: 25)]
    public int $perPage = 25;

    public bool $showExportModal = false;

    public function updatedSearch(): void
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

    public function updatedPerPage(): void
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
     * @return array<int, array{key: string, label: string, default?: bool}>
     */
    #[Computed]
    public function exportFields(): array
    {
        return SupporterExportController::$fields;
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

    /**
     * @return \Closure(object): void
     */
    private function organizationDonationsConstraint(?Organization $org): \Closure
    {
        return function ($q) use ($org): void {
            if (! $org) {
                $q->whereRaw('1 = 0');

                return;
            }

            $q->whereIn('campaign_id', function ($cq) use ($org): void {
                $cq->select('id')
                    ->from('campaigns')
                    ->where('organization_id', $org->id);
            });
        };
    }

    private function baseQuery(): Builder
    {
        $org = $this->organization;
        $orgDonations = $this->organizationDonationsConstraint($org);

        $query = Donor::query()
            ->select('donors.*')
            ->when($org, function (Builder $q) use ($org): void {
                $q->whereHas('donations.campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id));
            })
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->withCount(['donations as donations_count' => $orgDonations])
            ->withMin(['donations as donations_min_created_at' => $orgDonations], 'created_at')
            ->withMax(['donations as donations_max_created_at' => $orgDonations], 'created_at')
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.donor_id', 'donors.id')
                    ->tap($orgDonations)
                    ->select(Donation::reportSumColumn()),
                'lifetime_report_amount'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.donor_id', 'donors.id')
                    ->tap($orgDonations)
                    ->where('donations.currency', '!=', 'myr')
                    ->selectRaw('COUNT(*) > 0'),
                'has_report_approximation'
            );

        if (filled($this->search)) {
            $search = '%'.$this->search.'%';
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        [$start, $end] = $this->periodRange();

        if ($start !== null && $end !== null) {
            $query->whereHas('donations', function (Builder $q) use ($start, $end, $orgDonations): void {
                $orgDonations($q);
                $q->whereBetween('created_at', [$start, $end]);
            });
        }

        return $query;
    }

    #[Computed]
    public function donors()
    {
        $query = $this->baseQuery();

        $allowedSorts = ['name', 'email', 'donations_count', 'lifetime_report_amount', 'donations_min_created_at', 'donations_max_created_at', 'created_at'];
        $field = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'created_at';
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'desc';

        $query->orderBy($field, $direction);

        $perPage = in_array($this->perPage, [10, 25, 50, 100], true) ? $this->perPage : 25;

        return $query->paginate($perPage);
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    public function render()
    {
        $donors = $this->donors;
        $org = $this->organization;

        $exactAmounts = DB::table('donations')
            ->join('campaigns', 'campaigns.id', '=', 'donations.campaign_id')
            ->whereIn('donor_id', $donors->pluck('id'))
            ->when($org, fn ($q) => $q->where('campaigns.organization_id', $org->id))
            ->select('donor_id', 'currency', DB::raw('ROUND(SUM(gross_amount), 2) as total'))
            ->groupBy('donor_id', 'currency')
            ->get()
            ->groupBy('donor_id')
            ->map(fn ($items) => $items->mapWithKeys(fn ($item) => [strtoupper($item->currency) => $item->total]));

        return view('livewire.app.supporters.index', [
            'title' => 'Supporters',
            'exactAmounts' => $exactAmounts,
        ]);
    }
}
