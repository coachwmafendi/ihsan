<?php

declare(strict_types=1);

namespace App\Livewire\App\Elements;

use App\Models\Element;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ElementIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $typeFilter = '';

    public string $sortField = 'created_at';

    public string $sortDirection = 'desc';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedTypeFilter(): void
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

        $query = Element::query()
            ->when($org, fn (Builder $q) => $q->where('organization_id', $org->id))
            ->when(! $org, fn (Builder $q) => $q->whereRaw('1 = 0'))
            ->with('campaign');

        if (filled($this->search)) {
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        if (filled($this->typeFilter)) {
            $query->where('type', $this->typeFilter);
        }

        return $query;
    }

    #[Computed]
    public function elements()
    {
        $query = $this->baseQuery();

        $allowedSorts = ['name', 'type', 'campaign', 'is_active', 'created_at'];
        $field = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'created_at';
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'desc';

        if ($field === 'campaign') {
            $query->leftJoin('campaigns', 'campaigns.id', '=', 'elements.campaign_id')
                ->orderBy('campaigns.title', $direction)
                ->select('elements.*');
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
        return view('livewire.app.elements.index', [
            'title' => 'Elements',
        ]);
    }
}
