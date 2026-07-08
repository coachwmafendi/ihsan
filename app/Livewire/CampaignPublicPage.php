<?php

namespace App\Livewire;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Services\TrackingScriptService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Support Our Campaign')]
class CampaignPublicPage extends Component
{
    public Campaign $campaign;

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function progress(): array
    {
        $raised = (float) $this->campaign->collected_amount;
        $target = (float) ($this->campaign->target_amount ?? 0);
        $progressPercent = $target > 0 ? min(100, ($raised / $target) * 100) : 0;
        $checkpoints = [0, $target * 0.1, $target * 0.25, $target * 0.5, $target];

        $segments = [];
        for ($i = 1; $i < count($checkpoints); $i++) {
            $start = $checkpoints[$i - 1];
            $end = $checkpoints[$i];
            $range = $end - $start;
            $fill = match (true) {
                $range <= 0 => 0,
                $raised >= $end => 100,
                $raised <= $start => 0,
                default => (($raised - $start) / $range) * 100,
            };
            $segments[] = ['start' => $start, 'end' => $end, 'range' => $range, 'fill' => $fill];
        }

        $currentCheckpointIndex = null;
        foreach ($checkpoints as $index => $amount) {
            if ($amount > $raised) {
                $currentCheckpointIndex = $index;
                break;
            }
        }

        $goalReached = $currentCheckpointIndex === null && $raised >= $target && $target > 0;
        $currentCheckpointAmount = $checkpoints[$currentCheckpointIndex] ?? $target;
        $leftToNext = max(0, $currentCheckpointAmount - $raised);

        return [
            'raised' => $raised,
            'target' => $target,
            'progressPercent' => $progressPercent,
            'checkpoints' => $checkpoints,
            'segments' => $segments,
            'currentCheckpointIndex' => $currentCheckpointIndex,
            'currentCheckpointAmount' => $currentCheckpointAmount,
            'leftToNext' => $leftToNext,
            'goalReached' => $goalReached,
        ];
    }

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
