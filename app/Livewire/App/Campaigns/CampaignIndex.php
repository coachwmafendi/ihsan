<?php

declare(strict_types=1);

namespace App\Livewire\App\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CampaignIndex extends Component
{
    use WithPagination;

    public string $search = '';

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

    #[Computed]
    public function campaigns()
    {
        $org = $this->organization;

        if (! $org) {
            return Campaign::query()->whereRaw('1 = 0')->paginate(25);
        }

        $query = Campaign::query()
            ->where('organization_id', $org->id)
            ->withCount('donations');

        if (filled($this->search)) {
            $query->where('title', 'like', '%'.$this->search.'%');
        }

        if (filled($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $allowedSorts = ['title', 'status', 'created_at', 'collected_amount'];
        $field = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'created_at';
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'desc';

        $query->orderBy($field, $direction);

        return $query->paginate(25);
    }

    public function render()
    {
        return view('livewire.app.campaigns.index', [
            'title' => 'Campaigns',
        ]);
    }
}
