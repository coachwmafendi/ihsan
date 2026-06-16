<?php

declare(strict_types=1);

namespace App\Livewire\App\Supporters;

use App\Enums\SubscriptionStatus;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
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

    #[Computed]
    public function donorPortalUrl(): ?string
    {
        $org = Auth::user()?->organization;

        if (! $org) {
            return null;
        }

        $token = $this->donor->generateMagicToken();

        return route('donorportal.magic-login', ['organization' => $org->code, 'token' => $token]);
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
                ], $stripeOptions);

                $this->donor->subscriptions()
                    ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Paused])
                    ->whereHas('campaign', fn (Builder $q) => $q->where('organization_id', $org->id))
                    ->each(function (Subscription $subscription) use ($stripeOptions): void {
                        if ($subscription->stripe_subscription_id === null) {
                            return;
                        }

                        StripeSubscription::update($subscription->stripe_subscription_id, [
                            'metadata' => [
                                'donor_name' => $this->donor->name,
                                'donor_email' => $this->donor->email,
                            ],
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
