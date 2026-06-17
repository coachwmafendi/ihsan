<?php

declare(strict_types=1);

namespace App\Livewire\App\Subscriptions;

use App\Actions\Stripe\ManageStripeSubscription;
use App\Enums\SubscriptionInterval;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Subscription;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Stripe\Invoice as StripeInvoice;
use Stripe\Stripe;

#[Layout('layouts.app')]
class SubscriptionShow extends Component
{
    public Subscription $subscription;

    public bool $showUpgradeModal = false;

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

        $this->skipDuration = '1';
        $this->customSkipMonths = 1;
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
        $query = $this->subscription->donations();

        return [
            'amount' => (float) $query->sum(Donation::reportAmountColumn()),
            'hasApproximation' => Donation::hasReportApproximations($query->getQuery()),
        ];
    }

    #[Computed]
    public function originalAmounts(): Collection
    {
        return $this->subscription->donations()
            ->selectRaw('currency, ROUND(SUM(gross_amount), 2) as total')
            ->groupBy('currency')
            ->get()
            ->mapWithKeys(fn ($item) => [strtoupper($item->currency) => (float) $item->total]);
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
    public function nextInstallmentNumber(): int
    {
        return ((int) $this->subscription->payment_count) + 1;
    }

    #[Computed]
    public function nextInstallmentDate(): ?CarbonImmutable
    {
        $periodEnd = $this->subscription->current_period_end;

        if ($periodEnd === null) {
            return null;
        }

        $periodEnd = CarbonImmutable::parse($periodEnd);

        while ($periodEnd->isPast()) {
            $periodEnd = match ($this->subscription->interval) {
                SubscriptionInterval::Weekly => $periodEnd->addWeek(),
                SubscriptionInterval::Yearly => $periodEnd->addYear(),
                default => $periodEnd->addMonth(),
            };
        }

        return $periodEnd;
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
        $this->showUpgradeModal = true;
    }

    public function closeUpgradeModal(): void
    {
        $this->showUpgradeModal = false;
    }

    public function upgradeUrl(): string
    {
        $organization = $this->subscription->campaign?->organization;

        if (! $organization) {
            return '';
        }

        return URL::temporarySignedRoute(
            'donorportal.subscriptions.increase-link',
            now()->addDays(7),
            ['organization' => $organization, 'subscription' => $this->subscription],
        );
    }

    public bool $showCancelModal = false;

    public string $cancelReason = '';

    public string $cancelDetails = '';

    public function openCancelModal(): void
    {
        $this->cancelReason = '';
        $this->cancelDetails = '';
        $this->showCancelModal = true;
    }

    public function closeCancelModal(): void
    {
        $this->showCancelModal = false;
    }

    public function cancelSubscription(): void
    {
        $this->validate([
            'cancelReason' => 'nullable|string|max:255',
            'cancelDetails' => 'nullable|string|max:1000',
        ]);

        try {
            app(ManageStripeSubscription::class)->cancel($this->subscription, false);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Unable to cancel subscription. Please try again.');

            return;
        }

        $reason = $this->cancelReason;
        if ($this->cancelDetails) {
            $reason = $this->cancelReason ? $this->cancelReason.': '.$this->cancelDetails : $this->cancelDetails;
        }

        if ($reason) {
            $this->subscription->update(['cancellation_reason' => $reason]);
        }

        $this->subscription->refresh();
        $this->showCancelModal = false;
        $this->dispatch('notify', type: 'success', message: 'Subscription will cancel at the end of the billing period.');
    }

    public bool $showEditPaymentDetailsModal = false;

    public float $editAmount = 0.0;

    public string $editInterval = 'monthly';

    public int $editBillingDay = 1;

    public bool $editProcessPaymentNow = false;

    public ?string $editEndDate = null;

    public bool $editHasMaxPlanAmount = false;

    public ?float $editMaxPlanAmount = null;

    public bool $editHasMaxPlanInstallments = false;

    public ?int $editMaxPlanInstallments = null;

    public bool $editCoverFee = false;

    public string $editPaymentMethod = 'existing';

    public ?string $setupIntentClientSecret = null;

    public ?string $setupIntentStripeAccount = null;

    public function openEditPaymentDetailsModal(): void
    {
        $this->editAmount = (float) $this->subscription->amount;
        $this->editInterval = $this->subscription->interval->value;

        $start = $this->subscription->current_period_start ?? $this->subscription->created_at ?? now();
        $this->editBillingDay = min((int) $start->format('j'), 28);

        $this->editProcessPaymentNow = false;
        $this->editEndDate = $this->subscription->cancel_at?->format('Y-m-d');

        $this->editHasMaxPlanAmount = $this->subscription->max_plan_amount !== null;
        $this->editMaxPlanAmount = $this->subscription->max_plan_amount !== null
            ? (float) $this->subscription->max_plan_amount
            : (float) $this->subscription->amount;

        $this->editHasMaxPlanInstallments = $this->subscription->max_plan_installments !== null;
        $this->editMaxPlanInstallments = $this->subscription->max_plan_installments;

        $this->editCoverFee = (bool) $this->subscription->cover_fee;
        $this->editPaymentMethod = 'existing';
        $this->setupIntentClientSecret = null;
        $this->setupIntentStripeAccount = null;

        $this->showEditPaymentDetailsModal = true;
    }

    public function closeEditPaymentDetailsModal(): void
    {
        $this->showEditPaymentDetailsModal = false;
    }

    #[Computed]
    public function transactionCostEstimate(): float
    {
        $feePercent = (float) config('services.stripe.processing_fee_percent', 2.5) / 100;
        $fixedFees = ['myr' => 0.50, 'usd' => 0.30, 'sgd' => 0.50];
        $fixedFee = $fixedFees[strtolower($this->subscription->currency)] ?? 0.50;

        return round($this->editAmount * $feePercent + $fixedFee, 2);
    }

    #[Computed]
    public function nextInstallmentDisplay(): array
    {
        $fee = $this->editCoverFee ? $this->transactionCostEstimate() : 0.0;
        $amount = $this->editAmount + $fee;

        return [
            'amount' => $this->subscription->currency_symbol.' '.number_format($amount, 2),
            'date' => $this->estimatedNextInstallmentDate()?->format('M d, Y, g:i A') ?? '—',
        ];
    }

    private function estimatedNextInstallmentDate(): ?CarbonInterface
    {
        $base = $this->subscription->current_period_start ?? now();
        $day = $this->editBillingDay;

        if ($this->editInterval === 'monthly') {
            $candidate = $base->copy()->setDay($day)->startOfDay();

            return $candidate->isPast() ? $candidate->addMonth() : $candidate;
        }

        if ($this->editInterval === 'yearly') {
            $candidate = $base->copy()->setDay($day)->startOfDay();

            return $candidate->isPast() ? $candidate->addYear() : $candidate;
        }

        return $this->subscription->current_period_end;
    }

    public function updatedEditPaymentMethod(): void
    {
        if ($this->editPaymentMethod === 'new') {
            try {
                $secret = app(ManageStripeSubscription::class)->createSetupIntent($this->subscription);
                $this->setupIntentClientSecret = $secret;
                $this->setupIntentStripeAccount = $this->subscription->campaign?->organization?->stripe_account_id;
            } catch (\Exception $e) {
                $this->dispatch('notify', type: 'error', message: 'Unable to initialize new card form. Please try again.');
                $this->editPaymentMethod = 'existing';
            }

            return;
        }

        $this->setupIntentClientSecret = null;
        $this->setupIntentStripeAccount = null;
    }

    public function updatePaymentMethodFromJs(string $paymentMethodId): void
    {
        try {
            app(ManageStripeSubscription::class)->updatePaymentMethod($this->subscription, $paymentMethodId);
            $this->subscription->refresh();
            $this->dispatch('notify', type: 'success', message: 'Payment method updated.');
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Unable to update payment method. Please try again.');
        }
    }

    public function savePaymentDetails(): void
    {
        $this->validate([
            'editAmount' => 'required|numeric|min:1|max:99999.99',
            'editInterval' => 'required|in:monthly,weekly,yearly',
            'editBillingDay' => 'required|integer|min:1|max:28',
            'editEndDate' => 'nullable|date|after:today',
            'editMaxPlanAmount' => 'nullable|numeric|min:1|max:99999.99',
            'editMaxPlanInstallments' => 'nullable|integer|min:1',
        ]);

        try {
            $this->applyPaymentDetailsChanges();
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Unable to save changes. Please try again.');

            return;
        }

        $this->subscription->refresh();
        $this->closeEditPaymentDetailsModal();
        $this->dispatch('notify', type: 'success', message: 'Payment details updated successfully.');
    }

    private function applyPaymentDetailsChanges(): void
    {
        $manager = app(ManageStripeSubscription::class);

        $amountChanged = (float) $this->editAmount !== (float) $this->subscription->amount;

        if ($amountChanged) {
            $manager->changeAmount($this->subscription, (float) $this->editAmount, $this->editInterval);
            $this->subscription->refresh();
        }

        $manager->updateDetails($this->subscription, [
            'interval' => $this->subscription->interval->value,
            'billing_day' => $this->editBillingDay,
            'cancel_at' => $this->editEndDate,
            'cover_fee' => $this->editCoverFee,
        ]);

        $maxAmount = $this->editHasMaxPlanAmount ? (float) $this->editMaxPlanAmount : null;
        $maxInstallments = $this->editHasMaxPlanInstallments ? (int) $this->editMaxPlanInstallments : null;

        $this->subscription->update([
            'max_plan_amount' => $maxAmount,
            'max_plan_installments' => $maxInstallments,
        ]);

        if ($this->editProcessPaymentNow) {
            $this->createImmediateInvoice();
        }
    }

    private function createImmediateInvoice(): void
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        $this->subscription->loadMissing('campaign.organization');
        $stripeOptions = $this->stripeOptionsForSubscription($this->subscription);

        $invoice = StripeInvoice::create([
            'subscription' => $this->subscription->stripe_subscription_id,
            'auto_advance' => true,
            'collection_method' => 'charge_automatically',
        ], $stripeOptions);

        $paidInvoice = $invoice->pay([], $stripeOptions);

        if ($paidInvoice->status !== 'paid') {
            throw new \RuntimeException('Invoice was not paid.');
        }
    }

    /**
     * @return array<string, string>
     */
    private function stripeOptionsForSubscription(Subscription $subscription): array
    {
        $organization = $subscription->campaign?->organization;

        if ($organization?->stripe_account_id && $organization->stripe_onboarded) {
            return ['stripe_account' => $organization->stripe_account_id];
        }

        return [];
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

    public bool $showSkipModal = false;

    public string $skipDuration = '1';

    public $customSkipMonths = 1;

    public function openSkipModal(): void
    {
        $this->skipDuration = '1';
        $this->customSkipMonths = 1;
        $this->showSkipModal = true;
    }

    public function closeSkipModal(): void
    {
        $this->showSkipModal = false;
    }

    #[Computed]
    public function skipNextInstallmentDate(): ?CarbonInterface
    {
        $base = $this->subscription->current_period_end ?? now();

        return $base->copy()->addMonths($this->resolveSkipMonths());
    }

    public function confirmSkip(): void
    {
        $months = $this->resolveSkipMonths();

        try {
            app(ManageStripeSubscription::class)->pause($this->subscription, $months);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: 'Unable to skip installments. Please try again.');

            return;
        }

        $this->subscription->refresh();
        $this->showSkipModal = false;
        $this->dispatch('notify', type: 'success', message: 'Subscription skipped for '.$months.' month'.($months > 1 ? 's' : '').'.');
    }

    private function resolveSkipMonths(): int
    {
        if ($this->skipDuration === 'custom') {
            return max(1, min(12, (int) $this->customSkipMonths));
        }

        return (int) $this->skipDuration;
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

        $campaign = Campaign::find($this->editCampaignId);
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
