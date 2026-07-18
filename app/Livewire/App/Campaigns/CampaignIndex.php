<?php

declare(strict_types=1);

namespace App\Livewire\App\Campaigns;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Campaigns')]
class CampaignIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $sortField = 'status';

    public string $sortDirection = 'asc';

    public bool $showArchived = false;

    public bool $showRenameModal = false;

    public ?string $renameCampaignId = null;

    #[Validate('required|string|max:255')]
    public string $renameTitle = '';

    public function mount(): void
    {
        if (request()->boolean('create')) {
            $this->dispatch('open-create-campaign-modal');
        }
    }

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
            ->when(
                $this->showArchived,
                fn ($q) => $q->where('status', CampaignStatus::Archived),
                fn ($q) => $q->where('status', '!=', CampaignStatus::Archived->value)
            )
            ->withCount(['donations' => fn ($query) => $query->where('status', DonationStatus::Succeeded)])
            ->withExists(['donations as has_non_myr_donations' => fn ($query) => $query->where('status', DonationStatus::Succeeded)->where('currency', '!=', 'myr')])
            ->with(['latestDonation', 'subscriptions' => fn ($query) => $query->where('status', SubscriptionStatus::Active)->select('id', 'campaign_id', 'amount', 'currency', 'interval')]);

        if (filled($this->search)) {
            $query->whereRaw('LOWER(title) LIKE ?', ['%'.strtolower($this->search).'%']);
        }

        if (! $this->showArchived && filled($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $allowedSorts = ['title', 'status', 'created_at', 'collected_amount'];
        $field = in_array($this->sortField, $allowedSorts, true) ? $this->sortField : 'created_at';
        $direction = in_array($this->sortDirection, ['asc', 'desc'], true) ? $this->sortDirection : 'desc';

        if ($this->sortField === 'status') {
            $dir = $direction === 'asc' ? 'ASC' : 'DESC';
            $query->orderByRaw("CASE status WHEN 'active' THEN 1 WHEN 'paused' THEN 2 WHEN 'draft' THEN 3 WHEN 'ended' THEN 4 WHEN 'archived' THEN 5 ELSE 6 END {$dir}");
        } else {
            $query->orderBy($field, $direction);
        }

        // Always apply secondary sort by newness for UX consistency
        $query->orderBy('created_at', 'desc');

        return $query->paginate(25);
    }

    #[Computed]
    public function archivedCount(): int
    {
        $org = $this->organization;

        return Campaign::query()
            ->when($org, fn ($q) => $q->where('organization_id', $org->id))
            ->when(! $org, fn ($q) => $q->whereRaw('1 = 0'))
            ->where('status', CampaignStatus::Archived)
            ->count();
    }

    public function openCreateModal(): void
    {
        $this->dispatch('open-create-campaign-modal');
    }

    public function toggleArchived(): void
    {
        $this->showArchived = ! $this->showArchived;
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function restore(string $publicId): void
    {
        $campaign = Campaign::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        $this->authorize('restore', $campaign);
        $campaign->restore();
        $this->dispatch('notify', message: 'Campaign restored to draft.', variant: 'success');
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

        $this->redirectRoute('app.campaigns.edit', $campaign, navigate: true);
    }

    public function openRenameModal(string $publicId): void
    {
        $campaign = Campaign::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        if ($campaign->organization_id !== $this->organization?->id) {
            abort(403);
        }

        $this->authorize('update', $campaign);

        $this->renameCampaignId = $publicId;
        $this->renameTitle = $campaign->title;
        $this->resetValidation();
        $this->showRenameModal = true;
    }

    public function saveRename(): void
    {
        $this->validate();

        $campaign = Campaign::query()
            ->where('public_id', $this->renameCampaignId)
            ->firstOrFail();

        if ($campaign->organization_id !== $this->organization?->id) {
            abort(403);
        }

        $this->authorize('update', $campaign);

        $campaign->update([
            'title' => $this->renameTitle,
        ]);

        $this->showRenameModal = false;
        $this->renameCampaignId = null;
        $this->renameTitle = '';
        $this->dispatch('notify', message: 'Campaign renamed successfully.', variant: 'success');
    }

    public function clone(string $publicId): void
    {
        $campaign = Campaign::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        if ($campaign->organization_id !== $this->organization?->id) {
            abort(403);
        }

        $this->authorize('create', Campaign::class);

        $copy = $campaign->replicate([
            'public_id',
            'slug',
            'form_parameter',
        ]);

        $copy->title = $campaign->title.' (Copy)';
        $copy->status = CampaignStatus::Draft;
        $copy->collected_amount = 0;
        $copy->milestones_notified = null;
        $copy->save();

        $this->dispatch('notify', message: 'Campaign duplicated successfully.', variant: 'success');

        $this->redirectRoute('app.campaigns.edit', $copy, navigate: true);
    }

    public function disable(string $publicId): void
    {
        $campaign = Campaign::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        if ($campaign->organization_id !== $this->organization?->id) {
            abort(403);
        }

        $this->authorize('update', $campaign);

        $campaign->update([
            'status' => CampaignStatus::Paused,
        ]);

        $this->dispatch('notify', message: 'Campaign disabled successfully.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.app.campaigns.index');
    }
}
