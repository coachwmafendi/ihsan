<?php

namespace App\Livewire;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Services\TrackingScriptService;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Support Our Campaign')]
class CampaignPublicPage extends Component
{
    public Campaign $campaign;

    public function mount(Campaign $campaign): void
    {
        abort_if($campaign->status !== CampaignStatus::Active || ! $campaign->campaign_page_enabled, 404);

        $this->campaign = $campaign->load('organization');
    }

    public function render()
    {
        return view('livewire.campaign-public-page')
            ->layout('layouts.donation', [
                'organization' => $this->campaign->organization,
                'trackingConfigs' => TrackingScriptService::config($this->campaign->organization),
            ])
            ->title('Support '.$this->campaign->title);
    }

    #[On('campaign-donation-received')]
    public function refreshCampaign(string $campaignPublicId): void
    {
        if ($campaignPublicId !== $this->campaign->public_id) {
            return;
        }

        $this->campaign = Campaign::query()->findOrFail($this->campaign->getKey())->load('organization');
    }

    public function pollCampaign(): void
    {
        $this->campaign = Campaign::query()->findOrFail($this->campaign->getKey())->load('organization');
    }
}
