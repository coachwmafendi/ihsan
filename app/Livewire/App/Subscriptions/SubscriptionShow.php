<?php

declare(strict_types=1);

namespace App\Livewire\App\Subscriptions;

use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SubscriptionShow extends Component
{
    public Subscription $subscription;

    public function mount(): void
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            abort(404);
        }

        $hasOrgCampaign = $this->subscription->campaign?->organization_id === $org->id;

        if (! $hasOrgCampaign) {
            abort(404);
        }
    }

    #[Computed]
    public function totalPaymentsCount(): int
    {
        return $this->subscription->donations()->count();
    }

    #[Computed]
    public function totalPaidAmount(): string
    {
        $sum = $this->subscription->donations()->sum('gross_amount');

        return $this->subscription->currency_symbol.' '.number_format((float) $sum, 2);
    }

    #[Computed]
    public function recentPayments()
    {
        return $this->subscription->donations()
            ->with('campaign')
            ->latest()
            ->limit(10)
            ->get();
    }

    public function render()
    {
        return view('livewire.app.subscriptions.show', [
            'title' => 'Subscription '.$this->subscription->public_id,
        ]);
    }
}
