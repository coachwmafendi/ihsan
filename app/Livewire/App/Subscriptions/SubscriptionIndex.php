<?php

declare(strict_types=1);

namespace App\Livewire\App\Subscriptions;

use App\Models\Subscription;
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

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
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

        $query = Subscription::query()
            ->when($org, function (Builder $q) use ($org): void {
                $q->whereHas('campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id));
            })
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->with(['campaign', 'donor']);

        if (filled($this->search)) {
            $search = '%'.$this->search.'%';
            $query->whereHas('donor', function (Builder $q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            });
        }

        if (filled($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
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

    public function render()
    {
        return view('livewire.app.subscriptions.index', [
            'title' => 'Subscriptions',
        ]);
    }
}
