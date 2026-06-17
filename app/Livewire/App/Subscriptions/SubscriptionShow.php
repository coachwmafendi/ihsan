<?php

declare(strict_types=1);

namespace App\Livewire\App\Subscriptions;

use App\Actions\Stripe\ManageStripeSubscription;
use App\Models\Donation;
use App\Models\Subscription;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SubscriptionShow extends Component
{
    public Subscription $subscription;

    public bool $showUpgradeModal = false;

    public float $newAmount = 0;

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
    public function totalMyrAmount(): array
    {
        $total = (float) $this->subscription->donations()->sum(Donation::reportAmountColumn());
        $hasApproximation = $this->subscription->donations()
            ->where('currency', '!=', 'myr')
            ->whereNotNull('base_amount')
            ->exists();

        return [
            'amount' => $total,
            'hasApproximation' => $hasApproximation,
        ];
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

    #[Computed]
    public function receiptDonations()
    {
        return $this->subscription->donations()
            ->latest()
            ->get();
    }

    #[Computed]
    public function latestDonation(): ?Donation
    {
        return $this->subscription->donations()->latest()->first();
    }

    #[Computed]
    public function lastInstallmentDate(): ?string
    {
        return $this->latestDonation?->created_at->format('M d, Y, g:i A');
    }

    public function formattedAmount(): string
    {
        return $this->subscription->currency_symbol.' '.number_format((float) $this->subscription->amount, 2).' '.strtoupper($this->subscription->currency);
    }

    public function frequencyLabel(): string
    {
        return ucfirst($this->subscription->interval->value);
    }

    public function feeCoveredLabel(): string
    {
        return $this->subscription->cover_fee ? 'Covered' : 'Not covered';
    }

    public function openUpgradeModal(): void
    {
        $this->newAmount = (float) $this->subscription->amount;
        $this->showUpgradeModal = true;
    }

    public function closeUpgradeModal(): void
    {
        $this->showUpgradeModal = false;
    }

    public function upgradeAmount(): void
    {
        $this->validate([
            'newAmount' => 'required|numeric|min:1|max:99999.99',
        ]);

        if ((float) $this->newAmount === (float) $this->subscription->amount) {
            $this->closeUpgradeModal();

            return;
        }

        try {
            app(ManageStripeSubscription::class)->changeAmount(
                $this->subscription,
                (float) $this->newAmount,
            );
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Unable to update subscription amount. Please try again.');

            return;
        }

        $this->subscription->refresh();
        $this->closeUpgradeModal();
        $this->dispatch('notify', type: 'success', message: 'Subscription amount updated successfully.');
    }

    public function cancelSubscription(): void
    {
        try {
            app(ManageStripeSubscription::class)->cancel($this->subscription, false);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Unable to cancel subscription. Please try again.');

            return;
        }

        $this->subscription->refresh();
        $this->dispatch('notify', type: 'success', message: 'Subscription will cancel at the end of the billing period.');
    }

    public function pauseSubscription(): void
    {
        try {
            app(ManageStripeSubscription::class)->pause($this->subscription);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Unable to pause subscription. Please try again.');

            return;
        }

        $this->subscription->refresh();
        $this->dispatch('notify', type: 'success', message: 'Subscription paused for one month.');
    }

    public bool $showEditModal = false;

    public string $editCampaignId = '';

    public function openEditModal(): void
    {
        $this->editCampaignId = (string) $this->subscription->campaign_id;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
    }

    public function saveSubscription(): void
    {
        $this->validate([
            'editCampaignId' => 'required|exists:campaigns,id',
        ]);

        $campaign = \App\Models\Campaign::find($this->editCampaignId);
        $org = Auth::user()?->organization;

        if (! $campaign || $campaign->organization_id !== $org?->id) {
            $this->dispatch('notify', type: 'error', message: 'Invalid campaign selected.');

            return;
        }

        $this->subscription->update(['campaign_id' => $this->editCampaignId]);
        $this->subscription->refresh();
        $this->closeEditModal();
        $this->dispatch('notify', type: 'success', message: 'Subscription campaign updated successfully.');
    }

    public bool $showEditPersonalModal = false;

    public string $editFirstName = '';

    public string $editLastName = '';

    public string $editEmail = '';

    public string $editPhone = '';

    public string $editAddressLine1 = '';

    public string $editAddressLine2 = '';

    public string $editAddressCity = '';

    public string $editAddressState = '';

    public string $editAddressPostalCode = '';

    public string $editCountry = '';

    public function openEditPersonalModal(): void
    {
        $donor = $this->subscription->donor;

        if (! $donor) {
            $this->dispatch('notify', type: 'error', message: 'No supporter information available.');

            return;
        }

        $nameParts = explode(' ', $donor->name, 2);
        $this->editFirstName = $nameParts[0] ?? '';
        $this->editLastName = $nameParts[1] ?? '';
        $this->editEmail = $donor->email ?? '';
        $this->editPhone = $donor->phone ?? '';
        $this->editAddressLine1 = $donor->address_line1 ?? '';
        $this->editAddressLine2 = $donor->address_line2 ?? '';
        $this->editAddressCity = $donor->address_city ?? '';
        $this->editAddressState = $donor->address_state ?? '';
        $this->editAddressPostalCode = $donor->address_postal_code ?? '';
        $this->editCountry = $donor->country ?? '';
        $this->showEditPersonalModal = true;
    }

    public function closeEditPersonalModal(): void
    {
        $this->showEditPersonalModal = false;
    }

    public function savePersonalInformation(): void
    {
        $donor = $this->subscription->donor;

        if (! $donor) {
            $this->dispatch('notify', type: 'error', message: 'No supporter information available.');

            return;
        }

        $this->validate([
            'editFirstName' => ['required', 'string', 'max:255'],
            'editLastName' => ['nullable', 'string', 'max:255'],
            'editEmail' => ['required', 'email', 'max:255'],
            'editPhone' => ['nullable', 'string', 'max:50'],
            'editAddressLine1' => ['nullable', 'string', 'max:255'],
            'editAddressLine2' => ['nullable', 'string', 'max:255'],
            'editAddressCity' => ['nullable', 'string', 'max:255'],
            'editAddressState' => ['nullable', 'string', 'max:255'],
            'editAddressPostalCode' => ['nullable', 'string', 'max:50'],
            'editCountry' => ['nullable', 'string', 'max:2'],
        ]);

        $donor->update([
            'name' => trim($this->editFirstName.' '.$this->editLastName),
            'email' => $this->editEmail,
            'phone' => $this->editPhone ?: null,
            'address_line1' => $this->editAddressLine1 ?: null,
            'address_line2' => $this->editAddressLine2 ?: null,
            'address_city' => $this->editAddressCity ?: null,
            'address_state' => $this->editAddressState ?: null,
            'address_postal_code' => $this->editAddressPostalCode ?: null,
            'country' => $this->editCountry ?: null,
        ]);

        $this->subscription->refresh();
        $this->showEditPersonalModal = false;
        $this->dispatch('notify', type: 'success', message: 'Personal information updated.');
    }

    public function render()
    {
        return view('livewire.app.subscriptions.show', [
            'title' => 'Subscription '.$this->subscription->public_id,
        ]);
    }
}
