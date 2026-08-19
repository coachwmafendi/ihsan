<?php

declare(strict_types=1);

namespace App\Livewire\App\Supporters;

use App\Actions\DonorEmailLog\PreviewDonorEmail;
use App\Actions\DonorEmailLog\ResendDonorEmail;
use App\Actions\Stripe\SyncDonorDetailsToStripe;
use App\Enums\DonationStatus;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class SupporterShow extends Component
{
    public Donor $donor;

    public bool $editing = false;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public bool $showPreviewModal = false;

    public ?int $previewLogId = null;

    public ?string $previewLogPublicId = null;

    public ?string $previewSubject = null;

    public ?string $previewSentAt = null;

    public ?string $previewFromName = null;

    public ?string $previewFromEmail = null;

    public ?string $previewHtml = null;

    public ?string $previewToEmail = null;

    public bool $showResendModal = false;

    public ?int $resendLogId = null;

    public ?string $resendRecipientEmail = null;

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
            'isApproximate' => Donation::hasReportApproximations($query->getQuery()),
        ];
    }

    #[Computed]
    public function originalAmounts(): Collection
    {
        return $this->scopedDonations()
            ->selectRaw('currency, ROUND(SUM(gross_amount), 2) as total')
            ->groupBy('currency')
            ->get()
            ->mapWithKeys(fn ($item) => [strtoupper($item->currency) => (float) $item->total]);
    }

    #[Computed]
    public function hasSubscriptions(): bool
    {
        return $this->donor->subscriptions()
            ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', Auth::user()?->organization?->id))
            ->exists();
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
            ->with(['campaign', 'subscription'])
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function receiptDonations()
    {
        return $this->scopedDonations()
            ->where('status', DonationStatus::Succeeded)
            ->with('campaign')
            ->latest()
            ->limit(25)
            ->get();
    }

    #[Computed]
    public function lastDonationDate(): ?string
    {
        $lastDonation = $this->scopedDonations()->latest()->first();

        return $lastDonation?->created_at->format('M d, Y');
    }

    public function openEditModal(): void
    {
        $this->firstName = $this->donor->first_name ?? '';
        $this->lastName = $this->donor->last_name ?? '';
        $this->email = $this->donor->email;
        $this->editing = true;
    }

    public function closeEditModal(): void
    {
        $this->editing = false;
    }

    public function save(): void
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            abort(404);
        }

        $validated = $this->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('donors', 'email')->ignore($this->donor)],
        ]);

        $oldEmail = $this->donor->email;
        $wasValidated = $this->donor->hasValidatedEmail();

        $this->donor->update([
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'],
            'email' => $validated['email'],
        ]);

        if ($validated['email'] !== $oldEmail && $wasValidated) {
            $this->donor->markEmailValidated();
        }

        // mount() already verified this donor has donated to $org, and all subscription
        // queries on this page are scoped to $org. Sync to that Stripe Connect account.
        app(SyncDonorDetailsToStripe::class)->sync($this->donor, $org);

        $this->editing = false;
    }

    #[Computed]
    public function donorLanguage(): ?string
    {
        return match ($this->donor->locale) {
            'ms' => 'Bahasa Melayu',
            'en' => 'English',
            default => $this->donor->locale ? ucfirst($this->donor->locale) : null,
        };
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

    /**
     * Cards on file, with the ones closest to lapsing first.
     *
     * A card that expires before the next installment fails the charge, so the
     * soonest expiry is the one worth acting on.
     */
    #[Computed]
    public function paymentMethods()
    {
        return $this->donor->paymentMethods()
            ->orderByRaw('exp_year is null, exp_year asc, exp_month asc')
            ->get();
    }

    #[Computed]
    public function emailLogs()
    {
        $orgId = Auth::user()?->organization?->getKey();

        return $this->donor->emailLogs()
            ->when($orgId, fn (Builder $q) => $q->where('organization_id', $orgId))
            ->latest('sent_at')
            ->limit(50)
            ->get();
    }

    public function confirmResend(int $id): void
    {
        $org = Auth::user()?->organization;

        if (! $org instanceof Organization) {
            abort(404);
        }

        $log = $this->donor->emailLogs()
            ->whereKey($id)
            ->when($org->getKey(), fn (Builder $q, int $orgId) => $q->where('organization_id', $orgId))
            ->firstOrFail();

        $this->resendLogId = $log->id;
        $this->resendRecipientEmail = $log->donor->email;
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
        $org = Auth::user()?->organization;

        if (! $org instanceof Organization) {
            abort(404);
        }

        $log = $this->donor->emailLogs()
            ->whereKey($id)
            ->when($org->getKey(), fn (Builder $q, int $orgId) => $q->where('organization_id', $orgId))
            ->firstOrFail();

        $newLog = app(ResendDonorEmail::class)->handle($log, $toEmail);

        if ($newLog === null) {
            $this->dispatch('notify', variant: 'danger', message: 'This email cannot be resent.');

            return;
        }

        $this->dispatch('notify', variant: 'success', message: 'Email queued to be resent.');
    }

    public function previewEmail(int $id): void
    {
        $org = Auth::user()?->organization;

        if (! $org instanceof Organization) {
            abort(404);
        }

        $log = $this->donor->emailLogs()
            ->whereKey($id)
            ->when($org->getKey(), fn (Builder $q, int $orgId) => $q->where('organization_id', $orgId))
            ->with(['donation.donor', 'donation.campaign.organization', 'subscription.donor', 'subscription.campaign.organization'])
            ->firstOrFail();

        $html = app(PreviewDonorEmail::class)->handle($log);

        if ($html === null) {
            $this->dispatch('notify', variant: 'danger', message: 'This email cannot be previewed.');

            return;
        }

        $this->previewLogId = $log->id;
        $this->previewLogPublicId = $log->public_id;
        $this->previewSubject = $log->subject;
        $this->previewSentAt = $log->sent_at ? myrTime($log->sent_at) : null;
        $this->previewFromName = $org->name;
        $this->previewFromEmail = noreply_email();
        $this->previewToEmail = $log->metadata['resent_to_email'] ?? $this->donor->email;
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
        $this->previewLogPublicId = null;
        $this->previewSubject = null;
        $this->previewSentAt = null;
        $this->previewFromName = null;
        $this->previewFromEmail = null;
        $this->previewToEmail = null;
        $this->previewHtml = null;
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
        return view('livewire.app.supporters.show')
            ->title("{$this->donor->name} - Supporter");
    }
}
