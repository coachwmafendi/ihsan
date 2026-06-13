<?php

declare(strict_types=1);

namespace App\Livewire\App\Campaigns;

use App\Models\Campaign;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CampaignShow extends Component
{
    public Campaign $campaign;

    #[Computed]
    public function amountRaised(): string
    {
        return 'RM '.number_format((float) $this->campaign->collected_amount, 2);
    }

    #[Computed]
    public function donationsCount(): int
    {
        return $this->campaign->donations()->count();
    }

    #[Computed]
    public function activeRecurringCount(): int
    {
        return $this->campaign->subscriptions()
            ->whereIn('status', ['active', 'trialing'])
            ->count();
    }

    #[Computed]
    public function lastDonation()
    {
        return $this->campaign->donations()
            ->with('donor')
            ->latest()
            ->first();
    }

    #[Computed]
    public function recentDonations()
    {
        return $this->campaign->donations()
            ->with('donor')
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function elements()
    {
        return $this->campaign->elements()
            ->select(['id', 'public_id', 'token', 'name', 'type', 'created_at'])
            ->get();
    }

    public function render()
    {
        return view('livewire.app.campaigns.show', [
            'title' => $this->campaign->title,
        ]);
    }
}
