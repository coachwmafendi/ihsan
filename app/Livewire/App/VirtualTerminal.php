<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Actions\Stripe\LoadDonorSavedCards;
use App\Actions\Stripe\ProcessVirtualTerminalDonation;
use App\Actions\Stripe\ProcessVirtualTerminalSubscription;
use App\Models\Campaign;
use App\Models\Donor;
use App\Models\Organization;
use App\Services\DonationFeeEstimator;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.virtual-terminal')]
class VirtualTerminal extends Component
{
    public ?string $preloadedSupporterPublicId = null;

    public ?Donor $searchedDonor = null;

    public ?Donor $preloadedSupporter = null;

    public ?Organization $organization = null;

    public string $stripePublishableKey = '';

    /** @var array<int, array<string, mixed>> */
    public array $savedCards = [];

    /** @var array<string, mixed> */
    public array $formData = [
        'campaign_id' => null,
        'frequency' => 'once',
        'amount' => '',
        'currency' => '',
        'scheduled_for' => null,
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'payment_method' => 'new_card',
        'payment_method_id' => '',
        'cover_fee' => true,
    ];

    /** @var array<string, mixed> */
    public array $flash = [];

    public function boot(): void
    {
        if (! auth()->check() || auth()->user()?->organization_id === null) {
            abort(403);
        }
    }

    public function mount(): void
    {
        $this->organization = auth()->user()?->organization;
        $this->stripePublishableKey = config('services.stripe.key');
        $this->preloadedSupporterPublicId = request()->query('vt-supporter');

        if ($this->preloadedSupporterPublicId) {
            $this->loadPreloadedSupporter();
        }

        if (! $this->formData['currency']) {
            $this->formData['currency'] = $this->acceptedCurrencies[0] ?? 'myr';
        }

        $this->loadDefaultCampaign();
        $this->formData['scheduled_for'] = now()->format('Y-m-d');
    }

    public function loadPreloadedSupporter(): void
    {
        $this->preloadedSupporter = Donor::query()
            ->where('public_id', $this->preloadedSupporterPublicId)
            ->first();

        if ($this->preloadedSupporter) {
            $this->formData['first_name'] = $this->preloadedSupporter->first_name ?? '';
            $this->formData['last_name'] = $this->preloadedSupporter->last_name ?? '';
            $this->formData['email'] = $this->preloadedSupporter->email;

            $lastDonationCurrency = $this->preloadedSupporter->donations()
                ->latest()
                ->value('currency');

            if ($lastDonationCurrency) {
                $acceptedCurrencies = $this->acceptedCurrencies;
                $lastCurrencyLower = strtolower($lastDonationCurrency);

                if (in_array($lastCurrencyLower, $acceptedCurrencies)) {
                    $this->formData['currency'] = $lastCurrencyLower;
                }
            }

            $this->loadSavedCards();
        }
    }

    public function loadSavedCards(): void
    {
        $donor = $this->preloadedSupporter;
        if (! $donor) {
            $this->savedCards = [];
            $this->formData['payment_method'] = 'new_card';

            return;
        }

        $this->savedCards = $donor->paymentMethods()
            ->orderByDesc('is_default')
            ->latest('id')
            ->get()
            ->map(fn ($paymentMethod): array => [
                'id' => $paymentMethod->stripe_payment_method_id,
                'brand' => ucfirst((string) $paymentMethod->brand),
                'last4' => $paymentMethod->last4,
                'exp_month' => $paymentMethod->exp_month,
                'exp_year' => $paymentMethod->exp_year,
            ])
            ->all();

        if ($this->savedCards === [] && $donor->stripe_customer_id && $this->organization) {
            try {
                $this->savedCards = app(LoadDonorSavedCards::class)->handle($donor, $this->organization);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        $this->selectDefaultSavedCard();
    }

    private function selectDefaultSavedCard(): void
    {
        if ($this->savedCards === []) {
            $this->formData['payment_method'] = 'new_card';

            return;
        }

        $savedCardIds = collect($this->savedCards)->pluck('id');

        if (! $savedCardIds->contains($this->formData['payment_method'])) {
            $this->formData['payment_method'] = (string) $savedCardIds->first();
        }
    }

    public function loadDefaultCampaign(): void
    {
        $defaultCampaign = Campaign::query()
            ->where('organization_id', $this->organization->getKey())
            ->where('status', 'active')
            ->latest()
            ->first();

        if ($defaultCampaign) {
            $this->formData['campaign_id'] = (string) $defaultCampaign->getKey();
        }
    }

    public function clearPreloadedSupporter(): void
    {
        $this->preloadedSupporter = null;
        $this->preloadedSupporterPublicId = null;
        $this->savedCards = [];
        $this->formData['first_name'] = '';
        $this->formData['last_name'] = '';
        $this->formData['email'] = '';
        $this->formData['payment_method'] = 'new_card';
        $this->formData['payment_method_id'] = '';
    }

    public function searchDonorByEmail(): void
    {
        $email = $this->formData['email'] ?? '';
        if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->searchedDonor = null;

            return;
        }

        $organization = $this->organization;

        $this->searchedDonor = Donor::query()
            ->where('email', $email)
            ->whereHas('donations.campaign', fn ($q) => $q->where('organization_id', $organization->getKey()))
            ->first();
    }

    public function loadSearchedDonor(): void
    {
        if ($this->searchedDonor) {
            $this->formData['first_name'] = $this->searchedDonor->first_name ?? '';
            $this->formData['last_name'] = $this->searchedDonor->last_name ?? '';
            $this->formData['email'] = $this->searchedDonor->email;
            $this->preloadedSupporter = $this->searchedDonor;
            $this->searchedDonor = null;
            $this->loadSavedCards();
        }
    }

    #[Computed]
    public function campaigns(): array
    {
        return Campaign::query()
            ->where('organization_id', $this->organization->getKey())
            ->where('status', 'active')
            ->pluck('title', 'id')
            ->toArray();
    }

    /**
     * The connected Stripe account to scope card tokenisation to, so
     * client-side PaymentMethods are created on the same account that
     * the charge runs on (direct charges).
     */
    #[Computed]
    public function connectedStripeAccountId(): ?string
    {
        return $this->organization?->stripe_onboarded
            ? $this->organization->stripe_account_id
            : null;
    }

    #[Computed]
    public function acceptedCurrencies(): array
    {
        $settings = $this->organization?->settings ?? [];
        $currencies = $settings['accepted_currencies'] ?? [];

        if (! empty($currencies)) {
            return collect($currencies)
                ->map(fn (string $currency): string => strtolower($currency))
                ->values()
                ->all();
        }

        return ['myr'];
    }

    #[Computed]
    public function estimatedFee(): float
    {
        $amount = (float) $this->formData['amount'];
        if ($amount <= 0 || empty($this->formData['cover_fee'])) {
            return 0.0;
        }

        return DonationFeeEstimator::estimate($amount, $this->formData['currency'], 'stripe');
    }

    public function getProcessingFeeEstimate(): string
    {
        return $this->getCurrency().' '.number_format($this->estimatedFee, 2);
    }

    public function getCurrency(): string
    {
        return strtoupper((string) ($this->formData['currency'] ?? 'myr'));
    }

    public function getTotalAmount(): string
    {
        $amount = (float) $this->formData['amount'];

        return $this->getCurrency().' '.number_format($amount, 2);
    }

    public function getGrandTotal(): string
    {
        $amount = (float) $this->formData['amount'];

        return $this->getCurrency().' '.number_format($amount + $this->estimatedFee, 2);
    }

    public function processDonation(): void
    {
        $data = $this->formData;
        $campaignId = (int) $data['campaign_id'];
        $validator = Validator::make($data, [
            'campaign_id' => ['required', "exists:campaigns,id,organization_id,{$this->organization->getKey()}"],
            'frequency' => ['required', 'in:once,monthly'],
            'amount' => ['required', 'numeric', 'min:1', 'max:99999.99'],
            'currency' => ['required', 'string', 'in:'.implode(',', $this->acceptedCurrencies)],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'payment_method' => ['nullable', 'string'],
            'payment_method_id' => ['nullable', 'string'],
            'cover_fee' => ['boolean'],
        ]);

        if ($validator->fails()) {
            $this->dispatch('vt-error', message: 'Please check your form.');

            return;
        }

        $data = $validator->validated();
        $formattedAmount = number_format((float) $data['amount'], 2);

        try {
            if ($data['frequency'] === 'once') {
                app(ProcessVirtualTerminalDonation::class)->handle(
                    campaignId: (int) $data['campaign_id'],
                    amount: (float) $data['amount'],
                    firstName: $data['first_name'],
                    lastName: $data['last_name'],
                    email: $data['email'],
                    organization: $this->organization,
                    currency: $data['currency'],
                    savedCardId: $data['payment_method'] !== 'new_card' ? $data['payment_method'] : null,
                    paymentMethodId: $data['payment_method_id'] ?? null,
                    source: 'virtual_terminal',
                    coverFee: (bool) ($data['cover_fee'] ?? false),
                );

                $this->dispatch('notify', message: "Donation of {$this->getCurrency()} {$formattedAmount} processed successfully.", variant: 'success');
            } else {
                app(ProcessVirtualTerminalSubscription::class)->handle(
                    campaignId: (int) $data['campaign_id'],
                    amount: (float) $data['amount'],
                    firstName: $data['first_name'],
                    lastName: $data['last_name'],
                    email: $data['email'],
                    organization: $this->organization,
                    currency: $data['currency'],
                    savedCardId: $data['payment_method'] !== 'new_card' ? $data['payment_method'] : null,
                    paymentMethodId: $data['payment_method_id'] ?? null,
                    source: 'virtual_terminal',
                    coverFee: (bool) ($data['cover_fee'] ?? false),
                );

                $this->dispatch('notify', message: "Monthly donation of {$this->getCurrency()} {$formattedAmount} set up successfully.", variant: 'success');
            }

            $this->resetForm();
        } catch (\Throwable $e) {
            report($e);

            $this->dispatch('notify', message: 'Payment failed. Please try again.', variant: 'danger');
        }
    }

    public function resetForm(): void
    {
        $this->formData = [
            'campaign_id' => $this->formData['campaign_id'],
            'frequency' => 'once',
            'amount' => '',
            'currency' => $this->formData['currency'],
            'scheduled_for' => null,
            'first_name' => $this->preloadedSupporter ? $this->formData['first_name'] : '',
            'last_name' => $this->preloadedSupporter ? $this->formData['last_name'] : '',
            'email' => $this->preloadedSupporter ? $this->formData['email'] : '',
            'payment_method' => $this->savedCards !== [] ? (string) collect($this->savedCards)->pluck('id')->first() : 'new_card',
            'payment_method_id' => '',
            'cover_fee' => $this->formData['cover_fee'] ?? true,
        ];
    }

    public function render()
    {
        return view('livewire.app.virtual-terminal', [
            'title' => 'Virtual Terminal',
        ]);
    }
}
