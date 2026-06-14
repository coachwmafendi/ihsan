<?php

declare(strict_types=1);

namespace App\Livewire\App\Supporters;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
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
            ->when($org, function (Builder $q) use ($org): void {
                $q->whereHas('donations.campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id));
            })
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->withCount('donations')
            ->withSum('donations', 'gross_amount');

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

        $allowedSorts = ['name', 'email', 'donations_count', 'donations_sum_gross_amount', 'created_at'];
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
        return view('livewire.app.supporters.index', [
            'title' => 'Supporters',
        ]);
    }
}
