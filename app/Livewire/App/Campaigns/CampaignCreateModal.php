<?php

declare(strict_types=1);

namespace App\Livewire\App\Campaigns;

use App\Enums\CampaignStatus;
use App\Enums\ElementType;
use App\Enums\PaymentGateway;
use App\Models\Campaign;
use App\Models\Element;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CampaignCreateModal extends Component
{
    public bool $showCreateModal = false;

    public string $createMode = 'new';

    #[Validate('required|string|max:255')]
    public string $newCampaignName = '';

    public ?int $cloneCampaignId = null;

    public function updatedCreateMode(): void
    {
        $this->newCampaignName = '';
        $this->cloneCampaignId = null;
    }

    public function updatedCloneCampaignId(): void
    {
        if ($this->cloneCampaignId) {
            $source = Campaign::query()
                ->where('organization_id', $this->organization?->id)
                ->find($this->cloneCampaignId);

            if ($source) {
                $this->newCampaignName = $source->title.' (Copy)';
            }

            return;
        }

        $this->newCampaignName = '';
    }

    #[Computed]
    public function organization(): ?Organization
    {
        return Auth::user()?->organization;
    }

    #[Computed]
    public function cloneableCampaigns(): Collection
    {
        $org = $this->organization;

        if (! $org) {
            return new Collection;
        }

        return Campaign::query()
            ->where('organization_id', $org->id)
            ->orderBy('title')
            ->get(['id', 'title']);
    }

    #[On('open-create-campaign-modal')]
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

    public function createCampaign(): void
    {
        $this->authorize('create', Campaign::class);

        $validated = $this->validate();

        $org = $this->organization;

        if (! $org) {
            $this->dispatch('notify', message: 'Organization not found.', variant: 'danger');

            return;
        }

        try {
            $campaign = $this->createMode === 'clone'
                ? $this->cloneCampaign($validated)
                : $this->createNewCampaign($validated);
        } catch (\RuntimeException $e) {
            $this->dispatch('notify', message: $e->getMessage(), variant: 'danger');

            return;
        }

        $this->closeCreateModal();
        $this->dispatch('notify', message: 'Campaign created successfully.', variant: 'success');
        $this->redirectRoute('app.campaigns.edit', $campaign);
    }

    private function createNewCampaign(array $validated): Campaign
    {
        $campaign = Campaign::create([
            'organization_id' => $this->organization?->id,
            'title' => $validated['newCampaignName'],
            'status' => CampaignStatus::Draft,
            'allow_recurring' => true,
            'allow_custom_amount' => true,
            'payment_gateway' => PaymentGateway::Stripe->value,
            'config' => [
                'allow_cover_fee' => true,
                'show_comment' => true,
                'show_phone' => true,
            ],
        ]);

        $this->createDefaultDonorPortalButton($campaign);

        return $campaign;
    }

    private function cloneCampaign(array $validated): Campaign
    {
        if (! $this->cloneCampaignId) {
            throw new \RuntimeException('Please select a campaign to clone.');
        }

        $source = $this->resolveCloneSource($this->cloneCampaignId);

        if (! $source) {
            throw new \RuntimeException('Campaign not found.');
        }

        return Campaign::create([
            'organization_id' => $this->organization?->id,
            'title' => $validated['newCampaignName'],
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
            'campaign_page_enabled' => $source->campaign_page_enabled,
            'checkout_allowed_domains' => $source->checkout_allowed_domains,
        ]);
    }

    private function resolveCloneSource(int $id): ?Campaign
    {
        return Campaign::query()
            ->where('organization_id', $this->organization?->id)
            ->find($id);
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

    public function render(): View
    {
        return view('livewire.app.campaigns.create-modal');
    }
}
