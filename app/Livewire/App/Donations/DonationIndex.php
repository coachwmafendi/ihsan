<?php

declare(strict_types=1);

namespace App\Livewire\App\Donations;

use App\Enums\DonationStatus;
use App\Http\Controllers\DonationExportController;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Element;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Donations')]
class DonationIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $statusFilter = '';

    #[Url(except: '')]
    public string $campaignFilter = '';

    #[Url(except: '')]
    public string $frequencyFilter = '';

    /** @var array<int, string> */
    #[Url(except: [])]
    public array $sourceFilter = [];

    /** @var array<int, string> */
    #[Url(except: [])]
    public array $elementFilter = [];

    /** @var array<int, string> */
    #[Url(except: [])]
    public array $paymentMethodFilter = [];

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

    public function updatedFrequencyFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSourceFilter(): void
    {
        $this->resetPage();
    }

    public function clearSourceFilter(): void
    {
        $this->sourceFilter = [];
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    public function sourceOptions(): array
    {
        return [
            'checkout_modal' => 'Checkout Modal',
            'campaign_page' => 'Campaign Page',
            'virtual_terminal' => 'Virtual Terminal',
        ];
    }

    public function sourceChipLabel(): string
    {
        return $this->summariseSelection('Source', array_values(array_intersect_key($this->sourceOptions(), array_flip($this->sourceFilter))));
    }

    public function updatedElementFilter(): void
    {
        $this->resetPage();
    }

    public function clearElementFilter(): void
    {
        $this->elementFilter = [];
        $this->resetPage();
    }

    #[Computed]
    public function elementOptions()
    {
        $org = $this->organization;

        if (! $org) {
            return collect();
        }

        return Element::where('organization_id', $org->id)
            ->orderBy('name')
            ->get(['id', 'name', 'token']);
    }

    public function elementChipLabel(): string
    {
        $selected = $this->elementOptions
            ->whereIn('token', $this->elementFilter)
            ->pluck('name')
            ->values()
            ->all();

        return $this->summariseSelection('Element', $selected);
    }

    public function updatedPaymentMethodFilter(): void
    {
        $this->resetPage();
    }

    public function clearPaymentMethodFilter(): void
    {
        $this->paymentMethodFilter = [];
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function paymentMethodOptions(): array
    {
        $org = $this->organization;

        if (! $org) {
            return [];
        }

        return Donation::query()
            ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', $org->id))
            ->whereNotNull('payment_method_type')
            ->distinct()
            ->orderBy('payment_method_type')
            ->pluck('payment_method_type')
            ->mapWithKeys(fn (string $type) => [$type => ucwords(str_replace('_', ' ', $type))])
            ->all();
    }

    public function paymentMethodChipLabel(): string
    {
        $selected = array_values(array_intersect_key($this->paymentMethodOptions, array_flip($this->paymentMethodFilter)));

        return $this->summariseSelection('Payment method', $selected);
    }

    /**
     * @param  array<int, string>  $selected
     */
    private function summariseSelection(string $fallback, array $selected): string
    {
        return match (count($selected)) {
            0 => $fallback,
            1 => $selected[0],
            default => $selected[0].' and '.(count($selected) - 1).' more',
        };
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

    public function hasActiveFilters(): bool
    {
        return $this->period !== 'all_time'
            || filled($this->search)
            || filled($this->statusFilter)
            || filled($this->campaignFilter)
            || filled($this->frequencyFilter)
            || $this->sourceFilter !== []
            || $this->elementFilter !== []
            || $this->paymentMethodFilter !== [];
    }

    public function resetFilters(): void
    {
        $this->reset([
            'search', 'statusFilter', 'campaignFilter', 'frequencyFilter',
            'sourceFilter', 'elementFilter', 'paymentMethodFilter',
            'period', 'dateFrom', 'dateTo',
        ]);
        $this->resetPage();
    }

    public function redirectToShow(string $publicId): void
    {
        $this->redirectRoute('app.donations.show', $publicId, navigate: true);
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
        return DonationExportController::$fields;
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

        $query = Donation::query()
            ->when($org, function (Builder $q) use ($org): void {
                $q->whereHas('campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id));
            })
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->with(['campaign', 'donor', 'subscription']);

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

        if (filled($this->frequencyFilter)) {
            $query->where('type', $this->frequencyFilter);
        }

        if ($this->sourceFilter !== []) {
            $sources = array_intersect($this->sourceFilter, array_keys($this->sourceOptions()));

            // Element and legacy null sources surface as Checkout Modal.
            if (in_array('checkout_modal', $sources, true)) {
                $sources[] = 'element';
            }

            $query->where(function (Builder $q) use ($sources): void {
                $q->whereIn('donations.source', $sources);

                if (in_array('checkout_modal', $sources, true)) {
                    $q->orWhereNull('donations.source');
                }
            });
        }

        if ($this->elementFilter !== []) {
            $query->whereIn('donations.utm_params->element_token', $this->elementFilter);
        }

        if ($this->paymentMethodFilter !== []) {
            $query->whereIn('donations.payment_method_type', $this->paymentMethodFilter);
        }

        [$start, $end] = $this->periodRange();

        if ($start !== null && $end !== null) {
            $query->whereBetween('donations.created_at', [$start, $end]);
        }

        return $query;
    }

    #[Computed]
    public function donations()
    {
        $query = $this->baseQuery();

        $allowedSorts = ['created_at', 'donor_name', 'gross_amount', 'status', 'campaign'];
        $field = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'created_at';
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'desc';

        if ($field === 'donor_name') {
            $query->leftJoin('donors', 'donors.id', '=', 'donations.donor_id')
                ->orderBy('donors.name', $direction)
                ->select('donations.*');
        } elseif ($field === 'campaign') {
            $query->leftJoin('campaigns', 'campaigns.id', '=', 'donations.campaign_id')
                ->orderBy('campaigns.title', $direction)
                ->select('donations.*');
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
    public function totalAmount(): array
    {
        $query = $this->baseQuery();

        return [
            'amount' => (float) $query->sum(Donation::reportAmountColumn()),
            'isApproximate' => Donation::hasReportApproximations($this->baseQuery()),
        ];
    }

    #[Computed]
    public function newThisMonthCount(): int
    {
        return $this->baseQuery()->where('donations.created_at', '>=', now()->startOfMonth())->count();
    }

    #[Computed]
    public function avgDonation(): float
    {
        $count = $this->totalCount;

        return $count > 0 ? round($this->totalAmount['amount'] / $count, 2) : 0.0;
    }

    #[Computed]
    public function succeededCount(): int
    {
        return $this->baseQuery()->where('donations.status', DonationStatus::Succeeded)->count();
    }

    #[Computed]
    public function failedCount(): int
    {
        return $this->baseQuery()->where('donations.status', DonationStatus::Failed)->count();
    }

    #[Computed]
    public function refundedCount(): int
    {
        return $this->baseQuery()->where('donations.status', DonationStatus::Refunded)->count();
    }

    #[Computed]
    public function originalAmounts(): Collection
    {
        return $this->baseQuery()
            ->selectRaw('currency, ROUND(SUM(gross_amount), 2) as total')
            ->groupBy('currency')
            ->get()
            ->mapWithKeys(fn ($item) => [strtoupper($item->currency) => (float) $item->total]);
    }

    public function render()
    {
        return view('livewire.app.donations.index');
    }
}
