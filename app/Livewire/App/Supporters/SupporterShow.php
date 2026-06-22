<?php

declare(strict_types=1);

namespace App\Livewire\App\Supporters;

use App\Actions\DonorEmailLog\PreviewDonorEmail;
use App\Actions\DonorEmailLog\ResendDonorEmail;
use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\StripeMetadata;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

#[Layout('layouts.app')]
class SupporterShow extends Component
{
    public Donor $donor;

    public bool $editing = false;

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public bool $updateRecurringPlans = true;

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
            ->with('campaign')
            ->latest()
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function receiptDonations()
    {
        return $this->scopedDonations()
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
        $nameParts = explode(' ', $this->donor->name, 2);

        $this->firstName = $nameParts[0] ?? '';
        $this->lastName = $nameParts[1] ?? '';
        $this->email = $this->donor->email;
        $this->updateRecurringPlans = true;
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

        $name = trim($validated['firstName'].' '.$validated['lastName']);

        $this->donor->update([
            'name' => $name,
            'email' => $validated['email'],
        ]);

        if ($this->updateRecurringPlans && $this->donor->stripe_customer_id) {
            try {
                Stripe::setApiKey(config('services.stripe.secret'));

                $stripeOptions = $org->stripe_account_id
                    ? ['stripe_account' => $org->stripe_account_id]
                    : [];

                Customer::update($this->donor->stripe_customer_id, [
                    'name' => $name,
                    'email' => $validated['email'],
                    'preferred_locales' => StripeMetadata::customerLocale($this->donor) ?? [],
                ], $stripeOptions);

                $this->donor->subscriptions()
                    ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused])
                    ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', $org->id))
                    ->each(function (Subscription $subscription) use ($stripeOptions): void {
                        if ($subscription->stripe_subscription_id === null) {
                            return;
                        }

                        StripeSubscription::update($subscription->stripe_subscription_id, [
                            'metadata' => StripeMetadata::forDonorUpdate($this->donor),
                        ], $stripeOptions);
                    });
            } catch (\Exception $e) {
                report($e);
            }
        }

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
        $this->previewSubject = $log->subject;
        $this->previewSentAt = $log->sent_at ? myrTime($log->sent_at) : null;
        $this->previewFromName = $org->name;
        $this->previewFromEmail = config('mail.from.address', 'no-reply@getihsan.my');
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
        return view('livewire.app.supporters.show', [
            'title' => $this->donor->name,
        ]);
    }
}
