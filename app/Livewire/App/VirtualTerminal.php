<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class VirtualTerminal extends Component
{
    public ?string $campaign_id = null;

    public string $first_name = '';

    public string $last_name = '';

    public string $email = '';

    public ?string $phone = null;

    public string $amount = '';

    public string $currency = 'myr';

    public string $frequency = 'one_time';

    public string $payment_method = 'cash';

    public ?string $notes = null;

    public bool $cover_fee = false;

    public function mount(): void
    {
        if (! Auth::user()?->organization) {
            abort(403);
        }

        $currencies = $this->acceptedCurrencies();

        $this->currency = ! empty($currencies) ? $currencies[0] : 'myr';

        $this->loadDefaultCampaign();
    }

    public function loadDefaultCampaign(): void
    {
        $campaign = Campaign::where('organization_id', Auth::user()?->organization_id)
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($campaign) {
            $this->campaign_id = (string) $campaign->id;
        }
    }

    #[Computed]
    public function campaigns(): array
    {
        return Campaign::where('organization_id', Auth::user()?->organization_id)
            ->where('status', 'active')
            ->pluck('title', 'id')
            ->toArray();
    }

    #[Computed]
    public function acceptedCurrencies(): array
    {
        $settings = Auth::user()?->organization?->settings ?? [];
        $currencies = $settings['accepted_currencies'] ?? [];

        if (! empty($currencies)) {
            return collect($currencies)->map(fn (string $c): string => strtolower($c))->values()->all();
        }

        return ['myr'];
    }

    public function getSelectedCampaign(): ?Campaign
    {
        if (! $this->campaign_id) {
            return null;
        }

        return Campaign::find($this->campaign_id);
    }

    public function getMinimumAmount(): float
    {
        $campaign = $this->getSelectedCampaign();

        return (float) ($campaign?->minimum_amount ?? 1);
    }

    public function save(): void
    {
        $this->validate([
            'campaign_id' => ['required', 'exists:campaigns,id'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:'.$this->getMinimumAmount()],
            'currency' => ['required', 'string', 'in:'.implode(',', $this->acceptedCurrencies())],
            'frequency' => ['required', 'in:one_time,monthly'],
            'payment_method' => ['required', 'in:cash,bank_transfer,card,check'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $donor = Donor::firstOrCreate(
            ['email' => $this->email],
            [
                'name' => trim($this->first_name.' '.$this->last_name),
                'phone' => $this->phone,
            ]
        );

        $amount = (float) $this->amount;

        $donation = Donation::create([
            'organization_id' => $org->id,
            'campaign_id' => (int) $this->campaign_id,
            'donor_id' => $donor->id,
            'gross_amount' => $amount,
            'currency' => strtolower($this->currency),
            'status' => DonationStatus::Succeeded,
            'type' => $this->frequency === 'monthly' ? DonationType::Recurring : DonationType::OneTime,
            'payment_method_type' => $this->payment_method,
            'source' => 'virtual_terminal',
            'platform_fee_covered' => $this->cover_fee,
            'donor_message' => $this->notes,
        ]);

        if ($this->frequency === 'monthly') {
            Subscription::create([
                'organization_id' => $org->id,
                'campaign_id' => (int) $this->campaign_id,
                'donor_id' => $donor->id,
                'amount' => $amount,
                'currency' => strtolower($this->currency),
                'interval' => SubscriptionInterval::Monthly,
                'status' => SubscriptionStatus::Active,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
                'source' => 'virtual_terminal',
                'cover_fee' => $this->cover_fee,
            ]);
        }

        $this->dispatch('toast', type: 'success', message: 'Donation of '.strtoupper($this->currency).' '.number_format($amount, 2).' recorded successfully.');

        $this->reset(['first_name', 'last_name', 'email', 'phone', 'amount', 'notes', 'cover_fee']);
        $this->loadDefaultCampaign();
    }

    public function render()
    {
        return view('livewire.app.virtual-terminal', ['title' => 'Virtual Terminal']);
    }
}
