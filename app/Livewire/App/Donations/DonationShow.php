<?php

declare(strict_types=1);

namespace App\Livewire\App\Donations;

use App\Actions\Stripe\RefundDonation;
use App\Enums\DonationStatus;
use App\Models\Donation;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class DonationShow extends Component
{
    public Donation $donation;

    public bool $showRefundModal = false;

    public ?string $refundReason = null;

    public bool $showEditDonationModal = false;

    public ?int $editCampaignId = null;

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

    public bool $editIsAnonymous = false;

    #[Computed]
    public function netAmount(): string
    {
        return $this->donation->currency_symbol.' '.number_format((float) $this->donation->net_amount, 2);
    }

    /**
     * Display the donation amount in the original currency with an approximate
     * base-currency conversion when applicable (e.g. "$38.10 SGD ≈ MYR 118.54").
     */
    public function donationAmountDisplay(): string
    {
        $original = $this->donation->currency_symbol.' '.number_format((float) $this->donation->gross_amount, 2).' '.strtoupper($this->donation->currency);

        if ($this->donation->currency !== 'myr' && $this->donation->base_amount !== null) {
            return $original.' <span class="text-slate-400">≈ MYR '.number_format((float) $this->donation->base_amount, 2).'</span>';
        }

        return $original;
    }

    public function formattedOriginalAmount(): string
    {
        return $this->donation->currency_symbol.' '.number_format((float) $this->donation->gross_amount, 2).' '.strtoupper($this->donation->currency);
    }

    public function formattedBaseAmount(): string
    {
        return 'MYR '.number_format((float) ($this->donation->base_amount ?? $this->donation->gross_amount), 2);
    }

    public function beforeFeesCovered(): string
    {
        $amount = (float) $this->donation->gross_amount - (float) $this->donation->donor_fee_covered;

        return $this->donation->currency_symbol.' '.number_format($amount, 2).' '.strtoupper($this->donation->currency);
    }

    public function beforeFeesCoveredBase(): string
    {
        $ratio = (float) $this->donation->gross_amount > 0
            ? ((float) $this->donation->gross_amount - (float) $this->donation->donor_fee_covered) / (float) $this->donation->gross_amount
            : 0;
        $base = ((float) ($this->donation->base_amount ?? $this->donation->gross_amount)) * $ratio;

        return 'MYR '.number_format($base, 2);
    }

    public function platformFee(): string
    {
        return $this->donation->currency_symbol.' '.number_format((float) $this->donation->processing_fee, 2).' '.strtoupper($this->donation->currency);
    }

    public function platformFeeBase(): string
    {
        $base = $this->feeInBaseCurrency((float) $this->donation->processing_fee);

        return 'MYR '.number_format($base, 2);
    }

    public function paymentProcessingFee(): string
    {
        return $this->donation->currency_symbol.' '.number_format((float) $this->donation->stripe_fee, 2).' '.strtoupper($this->donation->currency);
    }

    public function payoutAmount(): string
    {
        return 'MYR '.number_format((float) ($this->donation->net_amount ?? $this->donation->base_amount ?? $this->donation->gross_amount), 2);
    }

    public function effectiveFeeRate(): string
    {
        $gross = (float) $this->donation->gross_amount;
        if ($gross <= 0) {
            return '0.00%';
        }

        $fees = (float) $this->donation->stripe_fee + (float) $this->donation->processing_fee - (float) $this->donation->donor_fee_covered;
        $rate = max(0, ($fees / $gross) * 100);

        return number_format($rate, 2).'%';
    }

    public function feeCoveredLabel(): string
    {
        return (float) $this->donation->donor_fee_covered > 0 ? 'Covered' : 'Not covered';
    }

    public function successDate(): ?string
    {
        if ($this->donation->status !== DonationStatus::Succeeded) {
            return null;
        }

        return $this->donation->updated_at?->format('M d, Y, H:i') ?? $this->donation->created_at->format('M d, Y, H:i');
    }

    public function frequencyLabel(): string
    {
        if ($this->donation->subscription) {
            return ucfirst($this->donation->subscription->interval->value);
        }

        return 'One-time';
    }

    public function subscriptionTotal(): string
    {
        if (! $this->donation->subscription) {
            return '—';
        }

        $total = (float) $this->donation->subscription->amount * (int) $this->donation->subscription->payment_count;

        return $this->donation->subscription->currency_symbol.' '.number_format($total, 2);
    }

    public function subscriptionPreviousInstallment(): ?string
    {
        if (! $this->donation->subscription) {
            return null;
        }

        $lastDonation = $this->donation->subscription->donations()->latest('created_at')->first();

        return $lastDonation?->created_at?->format('M d, Y, H:i');
    }

    public function subscriptionNextInstallment(): ?string
    {
        if (! $this->donation->subscription) {
            return null;
        }

        return $this->donation->subscription->current_period_end?->format('M d, Y, H:i');
    }

    private function feeInBaseCurrency(float $fee): float
    {
        $gross = (float) $this->donation->gross_amount;
        $base = (float) ($this->donation->base_amount ?? $gross);

        if ($gross <= 0) {
            return 0;
        }

        return $fee * ($base / $gross);
    }

    public function canRefund(): bool
    {
        return $this->donation->status === DonationStatus::Succeeded
            && filled($this->donation->stripe_charge_id);
    }

    public function openRefundModal(): void
    {
        if (! $this->canRefund()) {
            $this->dispatch('notify', message: 'This donation cannot be refunded.', variant: 'danger');

            return;
        }

        $this->showRefundModal = true;
    }

    public function cancelRefund(): void
    {
        $this->showRefundModal = false;
        $this->refundReason = null;
    }

    public function confirmRefund(): void
    {
        if (! $this->canRefund()) {
            $this->dispatch('notify', message: 'This donation cannot be refunded.', variant: 'danger');

            return;
        }

        $this->validate([
            'refundReason' => ['required', 'string', 'in:duplicate,fraud,requested_by_supporter,other'],
        ], [
            'refundReason.required' => 'Please select a refund reason.',
        ]);

        try {
            app(RefundDonation::class)->handle($this->donation);
            $this->showRefundModal = false;
            $this->refundReason = null;
            $this->dispatch('notify', message: 'Donation refunded successfully.', variant: 'success');
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('notify', message: 'Refund failed: '.$e->getMessage(), variant: 'danger');
        }
    }

    public function openEditDonationModal(): void
    {
        $this->editCampaignId = $this->donation->campaign_id;
        $this->showEditDonationModal = true;
    }

    public function cancelEditDonationModal(): void
    {
        $this->showEditDonationModal = false;
    }

    public function saveDonation(): void
    {
        $this->validate([
            'editCampaignId' => ['required', 'integer', 'exists:campaigns,id'],
        ]);

        $this->donation->update(['campaign_id' => $this->editCampaignId]);
        $this->showEditDonationModal = false;
        $this->dispatch('notify', message: 'Donation updated.', variant: 'success');
    }

    public function openEditPersonalModal(): void
    {
        $donor = $this->donation->donor;

        if (! $donor) {
            $this->dispatch('notify', message: 'No donor information available.', variant: 'danger');

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
        $this->editIsAnonymous = $this->donation->is_anonymous ?? false;
        $this->showEditPersonalModal = true;
    }

    public function cancelEditPersonalModal(): void
    {
        $this->showEditPersonalModal = false;
    }

    public function savePersonalInformation(): void
    {
        $donor = $this->donation->donor;

        if (! $donor) {
            $this->dispatch('notify', message: 'No donor information available.', variant: 'danger');

            return;
        }

        $validated = $this->validate([
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
            'editIsAnonymous' => ['boolean'],
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

        $this->donation->update(['is_anonymous' => $this->editIsAnonymous]);

        $this->showEditPersonalModal = false;
        $this->dispatch('notify', message: 'Personal information updated.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.app.donations.show', [
            'title' => 'Donation '.$this->donation->public_id,
        ]);
    }
}
