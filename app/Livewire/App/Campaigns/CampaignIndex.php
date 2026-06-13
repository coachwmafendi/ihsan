<?php

declare(strict_types=1);

namespace App\Livewire\App\Campaigns;

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Models\Campaign;
use App\Models\Element;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

    // Modal state
    public bool $showCreateModal = false;

    public string $createMode = 'new'; // 'new' or 'clone'

    public string $newCampaignName = '';

    public ?int $cloneCampaignId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedCreateMode(): void
    {
        $this->newCampaignName = '';
        $this->cloneCampaignId = null;
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

    #[Computed]
    public function cloneableCampaigns()
    {
        $org = $this->organization;

        if (! $org) {
            return collect();
        }

        return Campaign::query()
            ->where('organization_id', $org->id)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    public function openCreateModal(): void
    {
        $this->showCreateModal = true;
        $this->createMode = 'new';
        $this->newCampaignName = '';
        $this->cloneCampaignId = null;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->createMode = 'new';
        $this->newCampaignName = '';
        $this->cloneCampaignId = null;
    }

    public function updatedCloneCampaignId(): void
    {
        if ($this->cloneCampaignId) {
            $source = Campaign::find($this->cloneCampaignId);
            if ($source) {
                $this->newCampaignName = $source->title.' (Copy)';
            }
        }
    }

    public function createCampaign(): void
    {
        $org = $this->organization;

        if (! $org) {
            $this->dispatch('notify', message: 'Organization not found.', variant: 'danger');

            return;
        }

        if (blank($this->newCampaignName)) {
            $this->dispatch('notify', message: 'Campaign name is required.', variant: 'danger');

            return;
        }

        if ($this->createMode === 'clone') {
            if (! $this->cloneCampaignId) {
                $this->dispatch('notify', message: 'Please select a campaign to clone.', variant: 'danger');

                return;
            }

            $source = Campaign::find($this->cloneCampaignId);

            if (! $source || $source->organization_id !== $org->id) {
                $this->dispatch('notify', message: 'Campaign not found.', variant: 'danger');

                return;
            }

            $campaign = Campaign::create([
                'organization_id' => $org->id,
                'title' => $this->newCampaignName,
                'status' => CampaignStatus::Draft,
                'description' => $source->description,
                'headline' => $source->headline,
                'short_summary' => $source->short_summary,
                'has_target' => $source->has_target,
                'target_amount' => $source->has_target ? $source->target_amount : null,
                'has_end_date' => $source->has_end_date,
                'end_date' => $source->has_end_date ? $source->end_date : null,
                'allow_recurring' => $source->allow_recurring,
                'allow_custom_amount' => $source->allow_custom_amount,
                'minimum_amount' => $source->minimum_amount,
                'suggested_amounts' => $source->suggested_amounts,
                'suggested_amounts_one_time' => $source->suggested_amounts_one_time,
                'suggested_amounts_monthly' => $source->suggested_amounts_monthly,
                'default_monthly_amount' => $source->default_monthly_amount,
                'impact_descriptions_enabled' => $source->impact_descriptions_enabled,
                'thank_you_message' => $source->thank_you_message,
                'redirect_url' => $source->redirect_url,
                'config' => $source->config,
                'payment_gateway' => $source->payment_gateway,
                'checkout_modal_enabled' => $source->checkout_modal_enabled,
                'checkout_allowed_domains' => $source->checkout_allowed_domains,
            ]);
        } else {
            $campaign = Campaign::create([
                'organization_id' => $org->id,
                'title' => $this->newCampaignName,
                'status' => CampaignStatus::Draft,
                'allow_recurring' => true,
                'allow_custom_amount' => true,
                'config' => [
                    'allow_cover_fee' => true,
                    'show_comment' => true,
                ],
            ]);

            // Create default donor portal button for new campaign
            $this->createDefaultDonorPortalButton($campaign);
        }

        $this->closeCreateModal();
        $this->dispatch('notify', message: 'Campaign created successfully.', variant: 'success');
        $this->redirectRoute('app.campaigns.edit', $campaign);
    }

    private function createDefaultDonorPortalButton(Campaign $campaign): void
    {
        $orgId = $campaign->organization_id;

        $hasExistingPortalButton = Element::query()
            ->where('organization_id', $orgId)
            ->where('is_donor_portal_default', true)
            ->exists();

        if ($hasExistingPortalButton) {
            return;
        }

        Element::create([
            'organization_id' => $orgId,
            'campaign_id' => $campaign->getKey(),
            'name' => 'Dedicated Donorportal Button',
            'token' => Str::random(6),
            'type' => ElementType::Button,
            'config' => [
                'button_text' => 'Make a new donation',
                'button_color' => 'bg-blue-600 hover:bg-blue-700',
                'button_size' => 'text-base px-6 py-3',
                'corner_radius' => 8,
                'button_effect' => 'none',
                'action' => 'checkout_modal',
            ],
            'is_active' => true,
            'is_donor_portal_default' => true,
        ]);
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
