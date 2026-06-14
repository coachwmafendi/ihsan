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

    public string $sortField = 'status';

    public string $sortDirection = 'asc';

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

        if ($this->sortField === 'status') {
            $dir = $direction === 'asc' ? 'ASC' : 'DESC';
            // Custom status order: Active > Paused > Draft > Ended
            $query->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 WHEN 'ended' THEN 4 ELSE 5 END {$dir}");
        } else {
            $query->orderBy($field, $direction);
        }

        // Always apply secondary sort by newness for UX consistency
        $query->orderBy('created_at', 'desc');

        return $query->paginate(25);
    }

    public function openCreateModal(): void
    {
        $this->dispatch('open-create-campaign-modal');
    }

    public function redirectToEdit(string $publicId): void
    {
        $campaign = Campaign::query()
            ->where('public_id', $publicId)
            ->where('organization_id', $this->organization?->id)
            ->first();

        if (! $campaign) {
            $this->dispatch('notify', message: 'Campaign not found.', variant: 'danger');

            return;
        }

        $this->redirectRoute('app.campaigns.edit', $campaign);
    }

    public function render()
    {
        return view('livewire.app.campaigns.index', [
            'title' => 'Campaigns',
        ]);
    }
}
