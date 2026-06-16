<?php

declare(strict_types=1);

namespace App\Livewire\App\Supporters;

use App\Models\Donation;
use App\Models\Donor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SupporterShow extends Component
{
    public Donor $donor;

    public function mount(): void
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            abort(404);
        }

        $hasOrgDonation = $this->donor->donations()
            ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', $org->id))
            ->exists();

        if (! $hasOrgDonation) {
            abort(404);
        }
    }

    #[Computed]
    public function totalDonationsCount(): int
    {
        return $this->scopedDonations()->count();
    }

    #[Computed]
    public function totalAmount(): array
    {
        $query = $this->scopedDonations();

        return [
            'amount' => (float) $query->sum(Donation::reportAmountColumn()),
            'isApproximate' => Donation::hasReportApproximations($query),
        ];
    }

    #[Computed]
    public function activeSubscriptionsCount(): int
    {
        return $this->donor->subscriptions()
            ->where('status', 'active')
            ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', Auth::user()?->organization?->id))
            ->count();
    }

    #[Computed]
    public function recentDonations()
    {
        return $this->scopedDonations()
            ->with('campaign')
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function recentSubscriptions()
    {
        return $this->donor->subscriptions()
            ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', Auth::user()?->organization?->id))
            ->with('campaign')
            ->latest()
            ->limit(10)
            ->get();
    }

    private function scopedDonations(): HasMany
    {
        return $this->donor->donations()
            ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', Auth::user()?->organization?->id));
    }

    #[Computed]
    public function fullAddress(): ?string
    {
        $parts = array_filter([
            $this->donor->address_line1,
            $this->donor->address_line2,
            $this->donor->address_city,
            $this->donor->address_state,
            $this->donor->address_postal_code,
            $this->donor->country,
        ]);

        if (empty($parts)) {
            return null;
        }

        return implode(', ', $parts);
    }

    public function render()
    {
        return view('livewire.app.supporters.show', [
            'title' => $this->donor->name,
        ]);
    }
}
