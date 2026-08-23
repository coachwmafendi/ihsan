<?php

declare(strict_types=1);

namespace App\Livewire\App\Donations;

use App\Actions\Chip\RefundDonation as ChipRefundDonation;
use App\Actions\DonorEmailLog\PreviewDonorEmail;
use App\Actions\DonorEmailLog\ResendDonorEmail;
use App\Actions\Stripe\RefundDonation as StripeRefundDonation;
use App\Actions\Stripe\SyncDonorDetailsToStripe;
use App\Enums\DonationStatus;
use App\Livewire\Concerns\ShowsActivityTimeline;
use App\Models\Donation;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Services\AuditLogQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Activitylog\Models\Activity;

#[Layout('layouts.app')]
#[Title('Donation')]
class DonationShow extends Component
{
    use ShowsActivityTimeline;

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

    public bool $showPreviewModal = false;

    public ?int $previewLogId = null;

    public ?string $previewSubject = null;

    public ?string $previewSentAt = null;

    public ?string $previewFromName = null;

    public ?string $previewFromEmail = null;

    public ?string $previewHtml = null;

    public ?string $previewToEmail = null;

    public bool $showResendModal = false;

    public ?int $resendLogId = null;

    public ?string $resendRecipientEmail = null;

    #[Computed]
    public function netAmount(): string
    {
        return $this->formatDisplayAmount((float) $this->donation->net_amount);
    }

    /**
     * Display the donation amount in the original currency with an approximate
     * base-currency conversion when applicable (e.g. "$38.10 SGD ≈ MYR 118.54").
     */
    public function donationAmountDisplay(): string
    {
        $original = $this->formatDisplayAmount((float) $this->donation->gross_amount);

        if ($this->donation->currency !== 'myr' && $this->donation->base_amount !== null) {
            return $original.' <span class="text-slate-400">≈ '.$this->formattedBaseAmount().'</span>';
        }

        return $original;
    }

    public function formattedOriginalAmount(): string
    {
        return $this->formatDisplayAmount((float) $this->donation->gross_amount);
    }

    public function formattedBaseAmount(): string
    {
        return $this->formatDisplayAmount((float) ($this->donation->base_amount ?? $this->donation->gross_amount), true);
    }

    /**
     * Total amount charged to the donor, including any fee cover
     * (gross_amount stores the donation only; donor_fee_covered is added on top).
     */
    public function paymentAmount(): string
    {
        $total = (float) $this->donation->gross_amount + (float) $this->donation->donor_fee_covered;

        return $this->formatDisplayAmount($total);
    }

    public function paymentAmountBase(): string
    {
        $base = (float) ($this->donation->base_amount ?? $this->donation->gross_amount)
            + $this->feeInBaseCurrency((float) $this->donation->donor_fee_covered);

        return $this->formatDisplayAmount($base, true);
    }

    public function beforeFeesCovered(): string
    {
        return $this->formattedOriginalAmount();
    }

    public function beforeFeesCoveredBase(): string
    {
        return $this->formattedBaseAmount();
    }

    public function feeCoveredAmount(): string
    {
        return $this->formatDisplayAmount((float) $this->donation->donor_fee_covered);
    }

    public function platformFee(): string
    {
        return $this->formatDisplayAmount($this->feeInDisplayCurrency((float) $this->donation->processing_fee));
    }

    public function platformFeeBase(): string
    {
        $processingFee = (float) $this->donation->processing_fee;

        if ($this->donation->base_amount !== null || strtolower($this->donation->currency) === 'myr') {
            return $this->formatDisplayAmount($processingFee, true);
        }

        return $this->formatDisplayAmount($this->feeInBaseCurrency($processingFee), true);
    }

    public function paymentProcessingFee(): string
    {
        $fee = $this->paymentProcessorLabel() === 'CHIP'
            ? (float) $this->donation->chip_fee
            : (float) $this->donation->stripe_fee;

        return $this->formatDisplayAmount($this->feeInDisplayCurrency($fee));
    }

    public function paymentProcessingFeeBase(): string
    {
        $isChip = $this->paymentProcessorLabel() === 'CHIP';
        $fee = $isChip
            ? (float) $this->donation->chip_fee
            : (float) $this->donation->stripe_fee;

        if (! $isChip && ($this->donation->base_amount !== null || strtolower($this->donation->currency) === 'myr')) {
            return $this->formatDisplayAmount($fee, true);
        }

        return $this->formatDisplayAmount($this->feeInBaseCurrency($fee), true);
    }

    public function payoutAmount(): string
    {
        $amount = (float) ($this->donation->net_amount ?? $this->donation->gross_amount);

        if ($this->donation->base_amount !== null) {
            return $this->formatDisplayAmount($amount, true);
        }

        return $this->formatDisplayAmount($amount);
    }

    public function effectiveFeeRate(): string
    {
        $base = (float) ($this->donation->base_amount ?? $this->donation->gross_amount);
        if ($base <= 0) {
            return '0.00%';
        }

        $donorFeeCovered = $this->donation->currency === 'myr'
            ? (float) $this->donation->donor_fee_covered
            : ((float) $this->donation->donor_fee_covered * (float) ($this->donation->exchange_rate ?? 1));

        $processorFee = $this->paymentProcessorLabel() === 'CHIP'
            ? (float) $this->donation->chip_fee
            : (float) $this->donation->stripe_fee;

        $fees = $processorFee + (float) $this->donation->processing_fee - $donorFeeCovered;
        $rate = max(0, ($fees / $base) * 100);

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

        return $this->donation->updated_at ? myrTime($this->donation->updated_at) : myrTime($this->donation->created_at);
    }

    public function refundDate(): ?string
    {
        return $this->donation->refunded_at ? myrTime($this->donation->refunded_at) : null;
    }

    public function statusBadgeClass(): string
    {
        return match ($this->donation->status) {
            DonationStatus::Succeeded => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
            DonationStatus::Pending => 'bg-amber-50 text-amber-700 ring-amber-600/20',
            DonationStatus::Failed => 'bg-red-50 text-red-700 ring-red-600/10',
            DonationStatus::Refunded => 'bg-slate-50 text-slate-600 ring-slate-500/20',
        };
    }

    public function statusLabel(): string
    {
        return $this->donation->status === DonationStatus::Succeeded
            ? 'Succeeded'
            : (string) str($this->donation->status->value)->headline();
    }

    #[Computed]
    public function isRefunded(): bool
    {
        return $this->donation->status === DonationStatus::Refunded;
    }

    #[Computed]
    public function isRecurring(): bool
    {
        return $this->donation->subscription !== null;
    }

    public function paymentProcessorLabel(): string
    {
        if (filled($this->donation->chip_purchase_id) || filled($this->donation->chip_recurring_token)) {
            return 'CHIP';
        }

        return 'Stripe';
    }

    public function paymentProcessorIcon(): string
    {
        if (filled($this->donation->chip_purchase_id) || filled($this->donation->chip_recurring_token)) {
            return 'icons.chip';
        }

        return 'icons.stripe';
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

        $subscription = $this->donation->subscription;
        $total = (float) $subscription->amount * (int) $subscription->payment_count;

        return $subscription->displayAmount($total);
    }

    public function subscriptionPreviousInstallment(): ?string
    {
        if (! $this->donation->subscription) {
            return null;
        }

        $lastDonation = $this->donation->subscription->donations()->latest('created_at')->first();

        return $lastDonation?->created_at ? myrTime($lastDonation->created_at) : null;
    }

    public function subscriptionNextInstallment(): ?string
    {
        if (! $this->donation->subscription) {
            return null;
        }

        return $this->donation->subscription->current_period_end ? myrTime($this->donation->subscription->current_period_end) : null;
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

    private function feeInDisplayCurrency(float $fee): float
    {
        if ($this->donation->currency === 'myr' || $this->donation->exchange_rate === null || (float) $this->donation->exchange_rate <= 0) {
            return $fee;
        }

        return $fee / (float) $this->donation->exchange_rate;
    }

    /**
     * Format an amount for display. MYR donations use "MYR {amount}" to avoid the
     * redundant "RM {amount} MYR" layout. Foreign currencies keep their symbol
     * and ISO code (e.g. "$ 38.10 USD").
     */
    private function formatDisplayAmount(float $amount, bool $asBaseCurrency = false): string
    {
        return $this->donation->displayAmount($amount, $asBaseCurrency);
    }

    public function canRefund(): bool
    {
        return $this->donation->status === DonationStatus::Succeeded
            && (filled($this->donation->stripe_charge_id) || filled($this->donation->chip_purchase_id));
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
            if (filled($this->donation->chip_purchase_id)) {
                app(ChipRefundDonation::class)->handle($this->donation);
            } else {
                app(StripeRefundDonation::class)->handle($this->donation);
            }

            $this->donation->refresh();
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

        $this->editFirstName = $donor->first_name ?? '';
        $this->editLastName = $donor->last_name ?? '';
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
            'first_name' => $this->editFirstName,
            'last_name' => $this->editLastName,
            'email' => $this->editEmail,
            'phone' => $this->editPhone ?: null,
            'address_line1' => $this->editAddressLine1 ?: null,
            'address_line2' => $this->editAddressLine2 ?: null,
            'address_city' => $this->editAddressCity ?: null,
            'address_state' => $this->editAddressState ?: null,
            'address_postal_code' => $this->editAddressPostalCode ?: null,
            'country' => $this->editCountry ?: null,
        ]);

        // Use the campaign organization (the Stripe Connect account owner) rather than
        // the logged-in user's organization, so cross-organization users can't sync into
        // the wrong Stripe account.
        app(SyncDonorDetailsToStripe::class)->sync($donor, $this->donation->campaign->organization);

        $this->donation->update(['is_anonymous' => $this->editIsAnonymous]);

        $this->showEditPersonalModal = false;
        $this->dispatch('notify', message: 'Personal information updated.', variant: 'success');
    }

    /**
     * @return Collection<int, Activity>
     */
    #[Computed]
    public function activities(): Collection
    {
        return $this->activityTimeline(AuditLogQuery::forSubject($this->donation));
    }

    #[Computed]
    public function hasMoreActivity(): bool
    {
        return $this->activityTimelineHasMore(AuditLogQuery::forSubject($this->donation));
    }

    #[Computed]
    public function emailLogs()
    {
        $orgId = Auth::user()?->organization?->getKey();

        return $this->donation->emailLogs()
            ->when($orgId, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->latest('sent_at')
            ->limit(50)
            ->get();
    }

    public function confirmResend(int $id): void
    {
        $log = $this->findEmailLog($id);

        $this->resendLogId = $log->id;
        $this->resendRecipientEmail = $log->donor?->email;
        $this->showResendModal = true;
    }

    public function closeResendModal(): void
    {
        $this->showResendModal = false;
        $this->resendLogId = null;
        $this->resendRecipientEmail = null;
    }

    public function resendConfirmed(): void
    {
        if ($this->resendLogId === null) {
            return;
        }

        $this->resendEmail($this->resendLogId, $this->resendRecipientEmail);
        $this->closeResendModal();
        $this->closePreviewModal();
    }

    private function resendEmail(int $id, ?string $toEmail = null): void
    {
        $log = $this->findEmailLog($id);

        $newLog = app(ResendDonorEmail::class)->handle($log, $toEmail);

        if ($newLog === null) {
            $this->dispatch('notify', variant: 'danger', message: 'This email cannot be resent.');

            return;
        }

        $this->dispatch('notify', variant: 'success', message: 'Email queued to be resent.');
    }

    public function previewEmail(int $id): void
    {
        $log = $this->findEmailLog($id, with: ['donor', 'donation.donor', 'donation.campaign.organization', 'subscription.donor', 'subscription.campaign.organization']);

        $html = app(PreviewDonorEmail::class)->handle($log);

        if ($html === null) {
            $this->dispatch('notify', variant: 'danger', message: 'This email cannot be previewed.');

            return;
        }

        $org = Auth::user()?->organization;

        $this->previewLogId = $log->id;
        $this->previewSubject = $log->subject;
        $this->previewSentAt = $log->sent_at ? myrTime($log->sent_at) : null;
        $this->previewFromName = $org?->name;
        $this->previewFromEmail = noreply_email();
        $this->previewToEmail = $log->metadata['resent_to_email'] ?? $log->donor?->email;
        $this->previewHtml = $html;
        $this->showPreviewModal = true;
    }

    public function resendFromModal(): void
    {
        if ($this->previewLogId === null) {
            return;
        }

        $this->confirmResend($this->previewLogId);
    }

    public function closePreviewModal(): void
    {
        $this->showPreviewModal = false;
        $this->previewLogId = null;
        $this->previewSubject = null;
        $this->previewSentAt = null;
        $this->previewFromName = null;
        $this->previewFromEmail = null;
        $this->previewToEmail = null;
        $this->previewHtml = null;
    }

    /**
     * @param  array<int, string>  $with
     */
    private function findEmailLog(int $id, array $with = []): DonorEmailLog
    {
        $org = Auth::user()?->organization;

        if (! $org instanceof Organization) {
            abort(404);
        }

        return DonorEmailLog::whereKey($id)
            ->where('donation_id', $this->donation->getKey())
            ->where('organization_id', $org->getKey())
            ->when($with !== [], fn (Builder $q) => $q->with($with))
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.app.donations.show');
    }
}
