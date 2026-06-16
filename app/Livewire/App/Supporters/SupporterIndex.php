<?php

declare(strict_types=1);

namespace App\Livewire\App\Supporters;

use App\Models\Donation;
use App\Models\Donor;
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

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
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

    private function baseQuery(): Builder
    {
        $org = $this->organization;

        $query = Donor::query()
            ->select('donors.*')
            ->when($org, function (Builder $q) use ($org): void {
                $q->whereHas('donations.campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id));
            })
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->withCount('donations')
            ->withMin('donations', 'created_at')
            ->withMax('donations', 'created_at')
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.donor_id', 'donors.id')
                    ->select(Donation::reportSumColumn()),
                'lifetime_report_amount'
            )
            ->selectSub(
                fn ($q) => $q->from('donations')
                    ->whereColumn('donations.donor_id', 'donors.id')
                    ->where('donations.currency', '!=', 'myr')
                    ->whereNotNull('donations.base_amount')
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

        return $query->paginate(25);
    }

    #[Computed]
    public function totalCount(): int
    {
        return $this->baseQuery()->count();
    }

    public function render()
    {
        $donors = $this->donors;

        $exactAmounts = DB::table('donations')
            ->whereIn('donor_id', $donors->pluck('id'))
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
