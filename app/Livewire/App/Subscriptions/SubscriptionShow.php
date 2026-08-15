<?php

declare(strict_types=1);

namespace App\Livewire\App\Subscriptions;

use App\Actions\DonorEmailLog\PreviewDonorEmail;
use App\Actions\DonorEmailLog\ResendDonorEmail;
use App\Actions\Stripe\CancelLocalRecurringPlan;
use App\Actions\Stripe\ChangeRecurringAmount;
use App\Actions\Stripe\ChargeRecurringInstallment as ChargeRecurringInstallmentAction;
use App\Actions\Stripe\ManageStripeSubscription;
use App\Actions\Stripe\PauseLocalRecurringPlan;
use App\Actions\Stripe\SyncDonorDetailsToStripe;
use App\Actions\Stripe\UpdateAppControlledPaymentMethod;
use App\Enums\SubscriptionStatus;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\DonorEmailLog;
use App\Models\Organization;
use App\Models\Subscription;
use App\Services\DonationFeeEstimator;
use App\Services\SubscriptionSchedule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Stripe\Invoice as StripeInvoice;
use Stripe\Stripe;

#[Layout('layouts.app')]
#[Title('Recurring Plan')]
class SubscriptionShow extends Component
{
    public Subscription $subscription;

    public bool $showUpgradeModal = false;

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

        return $this->subscription->displayAmount((float) $sum);
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
        if ($this->isTerminalStatus) {
            return null;
        }

        $baseDate = $this->subscription->next_charge_at ?? $this->subscription->current_period_end;

        if ($baseDate === null) {
            return null;
        }

        $date = CarbonImmutable::parse($baseDate);

        while ($date->isPast()) {
            $date = SubscriptionSchedule::nextChargeAt($date, $this->subscription->interval);
        }

        return $date;
    }

    #[Computed]
    public function lastInstallmentDate(): ?string
    {
        return $this->latestDonation?->created_at ? myrTime($this->latestDonation->created_at) : null;
    }

    #[Computed]
    public function isTerminalStatus(): bool
    {
        return in_array($this->subscription->status, [
            SubscriptionStatus::Cancelled,
            SubscriptionStatus::Failed,
            SubscriptionStatus::Completed,
        ], true);
    }

    #[Computed]
    public function isScheduledToCancel(): bool
    {
        return (bool) $this->subscription->is_scheduled_to_cancel;
    }

    #[Computed]
    public function canManageRecurringPlan(): bool
    {
        return $this->subscription->status === SubscriptionStatus::Active
            && ! $this->subscription->is_scheduled_to_cancel;
    }

    public function formattedAmount(): string
    {
        return $this->subscription->displayAmount();
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
        if (! $this->ensureCanManageRecurringPlan()) {
            return;
        }

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
        if (! $this->ensureCanManageRecurringPlan()) {
            return;
        }

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
        if (! $this->ensureCanManageRecurringPlan()) {
            return;
        }

        $this->validate([
            'cancelReason' => 'nullable|string|max:255',
            'cancelDetails' => 'nullable|string|max:1000',
        ]);

        try {
            app(CancelLocalRecurringPlan::class)->cancel($this->subscription);
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
        $this->dispatch('notify', type: 'success', message: 'Subscription cancelled.');
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
        if (! $this->ensureCanManageRecurringPlan()) {
            return;
        }

        $this->editAmount = (float) $this->subscription->amount;
        $this->editInterval = $this->subscription->interval->value;

        $chargeDate = $this->subscription->next_charge_at ?? $this->subscription->current_period_start ?? $this->subscription->created_at ?? now();
        $this->editBillingDay = min((int) $chargeDate->format('j'), 28);

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
        $gateway = $this->subscription->campaign?->payment_gateway?->value ?? 'stripe';

        return DonationFeeEstimator::estimate(
            (float) $this->editAmount,
            $this->subscription->currency,
            $gateway
        );
    }

    #[Computed]
    public function nextInstallmentDisplay(): array
    {
        $fee = $this->editCoverFee ? $this->transactionCostEstimate() : 0.0;
        $amount = $this->editAmount + $fee;

        return [
            'amount' => $this->subscription->displayAmount($amount),
            'date' => $this->estimatedNextInstallmentDate() ? myrTime($this->estimatedNextInstallmentDate()) : '—',
        ];
    }

    private function estimatedNextInstallmentDate(): ?CarbonInterface
    {
        $base = $this->subscription->next_charge_at ?? $this->subscription->current_period_start ?? now();
        $day = $this->editBillingDay;

        if ($this->editInterval === 'monthly') {
            $candidate = $base->copy()->setDay(min($day, $base->daysInMonth))->startOfDay();

            return $candidate->isPast() ? $candidate->addMonth() : $candidate;
        }

        if ($this->editInterval === 'yearly') {
            $candidate = $base->copy()->setDay(min($day, $base->daysInMonth))->startOfDay();

            return $candidate->isPast() ? $candidate->addYear() : $candidate;
        }

        if ($this->editInterval === 'weekly') {
            $candidate = $base->copy()->addWeek()->startOfDay();

            return $candidate;
        }

        return $this->subscription->next_charge_at ?? $this->subscription->current_period_end;
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
            if (filled($this->subscription->stripe_subscription_id)) {
                app(ManageStripeSubscription::class)->updatePaymentMethod($this->subscription, $paymentMethodId);
            } else {
                app(UpdateAppControlledPaymentMethod::class)->update($this->subscription, $paymentMethodId);
            }

            $this->subscription->refresh();
            $this->dispatch('notify', type: 'success', message: 'Payment method updated.');
        } catch (\Exception $e) {
            report($e);
            $this->dispatch('notify', type: 'error', message: 'Unable to update payment method. Please try again.');
        }
    }

    public function savePaymentDetails(): void
    {
        if (! $this->ensureCanManageRecurringPlan()) {
            return;
        }

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
        if (filled($this->subscription->stripe_subscription_id)) {
            $this->applyStripePaymentDetailsChanges();

            return;
        }

        $this->applyAppControlledPaymentDetailsChanges();
    }

    private function applyStripePaymentDetailsChanges(): void
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

        $this->updateMaxPlanLimits();

        if ($this->editProcessPaymentNow) {
            $this->createImmediateInvoice();
        }
    }

    private function applyAppControlledPaymentDetailsChanges(): void
    {
        $amountChanged = (float) $this->editAmount !== (float) $this->subscription->amount;

        if ($amountChanged) {
            app(ChangeRecurringAmount::class)->change($this->subscription, (float) $this->editAmount);
        }

        $updateData = [
            'interval' => $this->editInterval,
            'cover_fee' => $this->editCoverFee,
            'cancel_at' => $this->editEndDate ? CarbonImmutable::parse($this->editEndDate) : null,
        ];

        $intervalChanged = $this->editInterval !== $this->subscription->interval->value;
        $billingDayChanged = $this->editBillingDay !== (int) ($this->subscription->next_charge_at ?? now())->format('j');

        if ($intervalChanged || $billingDayChanged) {
            $updateData['next_charge_at'] = $this->calculateNextChargeAtFromEdits();
        }

        $this->subscription->update($updateData);

        $this->updateMaxPlanLimits();

        if ($this->editProcessPaymentNow) {
            app(ChargeRecurringInstallmentAction::class)->handle($this->subscription);
            $this->subscription->refresh();
        }
    }

    private function calculateNextChargeAtFromEdits(): CarbonImmutable
    {
        $base = CarbonImmutable::parse($this->subscription->next_charge_at ?? now());
        $candidate = $base->setDay(min($this->editBillingDay, $base->daysInMonth))->startOfDay();

        if ($candidate->isPast()) {
            $candidate = match ($this->editInterval) {
                'weekly' => $candidate->addWeek(),
                'yearly' => $candidate->addYear(),
                default => $candidate->addMonth(),
            };
        }

        return $candidate;
    }

    private function updateMaxPlanLimits(): void
    {
        $maxAmount = $this->editHasMaxPlanAmount ? (float) $this->editMaxPlanAmount : null;
        $maxInstallments = $this->editHasMaxPlanInstallments ? (int) $this->editMaxPlanInstallments : null;

        $this->subscription->update([
            'max_plan_amount' => $maxAmount,
            'max_plan_installments' => $maxInstallments,
        ]);
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
        if (! $this->ensureCanManageRecurringPlan()) {
            return;
        }

        try {
            if (filled($this->subscription->stripe_subscription_id)) {
                app(ManageStripeSubscription::class)->pause($this->subscription);
            } else {
                app(PauseLocalRecurringPlan::class)->pause($this->subscription);
            }
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
        if (! $this->ensureCanManageRecurringPlan()) {
            return;
        }

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
        $base = $this->subscription->next_charge_at ?? $this->subscription->current_period_end ?? now();

        return $base->copy()->addMonths($this->resolveSkipMonths());
    }

    public function confirmSkip(): void
    {
        if (! $this->ensureCanManageRecurringPlan()) {
            return;
        }

        $months = $this->resolveSkipMonths();

        try {
            if (filled($this->subscription->stripe_subscription_id)) {
                app(ManageStripeSubscription::class)->pause($this->subscription, $months);
            } else {
                app(PauseLocalRecurringPlan::class)->pause($this->subscription, $months);
            }
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

        if ($donor->stripe_customer_id) {
            // Use the campaign organization (the Stripe Connect account owner), not the
            // logged-in user's organization, so the sync targets the correct Stripe account.
            app(SyncDonorDetailsToStripe::class)->sync($donor, $this->subscription->campaign->organization);
        }

        $this->subscription->refresh();
        $this->showEditPersonalModal = false;
        $this->dispatch('notify', type: 'success', message: 'Personal information updated.');
    }

    #[Computed]
    public function emailLogs()
    {
        $orgId = Auth::user()?->organization?->getKey();

        return $this->subscription->emailLogs()
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

    private function ensureCanManageRecurringPlan(): bool
    {
        if (! $this->canManageRecurringPlan) {
            $this->dispatch('notify', type: 'error', message: 'This recurring plan cannot be modified.');

            return false;
        }

        return true;
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
            ->where('subscription_id', $this->subscription->getKey())
            ->where('organization_id', $org->getKey())
            ->when($with !== [], fn (Builder $q) => $q->with($with))
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.app.subscriptions.show');
    }
}
