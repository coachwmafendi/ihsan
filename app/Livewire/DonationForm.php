<?php

namespace App\Livewire;

use App\Actions\Stripe\CreatePaymentIntent;
use App\Actions\Stripe\CreateRecurringSubscription;
use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Jobs\SendCampaignMilestoneNotification;
use App\Jobs\SendDonationReceipt;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SyncDonationStripeDetailsJob;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Services\FraudDetectionService;
use App\Services\TrackingScriptService;
use App\Support\ClientInfo;
use App\Support\Currency;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Renderless;
use Livewire\Component;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\Stripe;

#[Title('Donation Form')]
class DonationForm extends Component
{
    public ?Element $element = null;

    public ?Campaign $campaign = null;

    public int|float|string $amount = 5;

    public string $frequency = 'monthly';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public bool $dedicate = false;

    public string $comment = '';

    public bool $isEmbed = false;

    public bool $isPopup = false;

    public string $pageUrl = '';

    public string $currency = 'myr';

    public bool $coverFee = true;

    public ?string $donationPublicId = null;

    /**
     * @return array<int, string>
     */
    public function getAcceptedCurrencies(): array
    {
        $organization = $this->element?->campaign?->organization ?? $this->campaign?->organization;

        if ($organization === null) {
            return ['myr'];
        }

        return $organization->settings['accepted_currencies'] ?? ['myr'];
    }

    public function selectCurrency(string $currency, bool $resetAmount = true): void
    {
        $accepted = $this->getAcceptedCurrencies();
        if (! in_array($currency, $accepted, true)) {
            return;
        }

        $this->currency = $currency;

        if ($resetAmount) {
            $amounts = $this->suggestedAmounts($this->frequency);
            $this->amount = $amounts[0] ?? $this->amount;
        }

        $this->dispatch('currency-updated',
            currency: $currency,
            symbol: Currency::symbol($currency),
            amount: $this->amount,
            oneTimeAmounts: $this->suggestedAmounts('one_time'),
            monthlyAmounts: $this->suggestedAmounts('monthly'),
        );
    }

    public function mount(?Element $element = null, ?Campaign $campaign = null): void
    {
        $route = request()->route();

        if ($element === null && $route !== null) {
            $element = $route->parameter('element');
        }

        if ($campaign === null && $route !== null) {
            $campaign = $route->parameter('campaign');
        }

        $this->pageUrl = request()->fullUrl();

        if ($element instanceof Element) {
            abort_if(
                ! $element->is_active || $element->campaign === null,
                404
            );

            $this->element = $element->loadMissing(['campaign.organization']);
            $this->amount = $this->config('default_amount', $this->suggestedAmounts()[0] ?? 5);
            $this->frequency = $this->config('default_frequency', $this->config('allow_monthly', true) ? 'monthly' : 'one_time');
            $this->setElementPresentationMode($element);
        } elseif ($campaign instanceof Campaign) {
            abort_if(
                $campaign->status !== CampaignStatus::Active || ! $campaign->checkout_modal_enabled,
                404
            );

            $this->campaign = $campaign->loadMissing(['organization']);
            $this->amount = $this->suggestedAmounts()[0] ?? 5;
            $this->frequency = $this->config('allow_monthly', true) ? 'monthly' : 'one_time';
            $this->setCampaignPresentationMode();
        } elseif ($this->element !== null) {
            // Direct initialization (tests) via pre-set property
            $element = $this->element;
            abort_if(
                ! $element->is_active || $element->campaign === null,
                404
            );

            $this->element = $element->loadMissing(['campaign.organization']);
            $this->amount = $this->config('default_amount', $this->suggestedAmounts()[0] ?? 5);
            $this->frequency = $this->config('default_frequency', $this->config('allow_monthly', true) ? 'monthly' : 'one_time');
            $this->setElementPresentationMode($element);
        } elseif ($this->campaign !== null) {
            // Direct initialization (tests) via pre-set property
            $campaign = $this->campaign;
            abort_if(
                $campaign->status !== CampaignStatus::Active || ! $campaign->checkout_modal_enabled,
                404
            );

            $this->campaign = $campaign->loadMissing(['organization']);
            $this->amount = $this->suggestedAmounts()[0] ?? 5;
            $this->frequency = $this->config('allow_monthly', true) ? 'monthly' : 'one_time';
            $this->setCampaignPresentationMode();
        } else {
            abort(404);
        }

        $this->overrideFromQueryParams();
    }

    private function overrideFromQueryParams(): void
    {
        $frequency = request()->query('frequency');
        if ($frequency !== null && in_array($frequency, ['one_time', 'monthly'], strict: true)) {
            $this->frequency = $frequency;
        }

        $amount = request()->query('amount');
        if ($amount !== null && is_numeric($amount) && (float) $amount > 0) {
            $this->amount = (float) $amount;
        }

        $currency = request()->query('currency');
        if ($currency !== null) {
            $this->selectCurrency(strtolower($currency), false);
        }

        $coverFee = request()->query('cover_fee');
        if ($coverFee !== null) {
            $this->coverFee = (bool) (int) $coverFee;
        }
    }

    private function setElementPresentationMode(Element $element): void
    {
        $this->isEmbed = request()->query('embed') !== null;
        $this->isPopup = $element->type === ElementType::Popup
            || request()->query('popup') !== null
            || (bool) $this->config('display_as_popup', false);
    }

    private function setCampaignPresentationMode(): void
    {
        $this->isEmbed = request()->query('embed') !== null;
        $this->isPopup = request()->query('popup') !== null;
    }

    public function selectAmount(int $amount): void
    {
        $this->amount = $amount;
        $this->dispatch('amount-updated', amount: $amount);
    }

    public function selectFrequency(string $frequency): void
    {
        if ($frequency === 'monthly' && ! $this->config('allow_monthly', true)) {
            return;
        }

        $this->frequency = $frequency;
        $amounts = $this->suggestedAmounts($frequency);
        $this->amount = $amounts[0] ?? $this->amount;
        $this->dispatch('amount-updated', amount: $this->amount);
    }

    public function confirmPayment(string $paymentIntentId, ?StripePaymentIntent $paymentIntent = null): void
    {
        $this->skipRender();

        Stripe::setApiKey(config('services.stripe.secret'));

        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->where('status', DonationStatus::Pending)
            ->first();

        if ($donation === null) {
            return;
        }

        try {
            $donation->loadMissing('campaign.organization');
            $stripeOptions = $this->stripeOptionsFor($donation);
            $synced = app(SyncDonationStripeDetails::class)->sync($donation, $paymentIntent, $stripeOptions);
            $paymentIntent = $synced['payment_intent'];

            $donation->update([
                'status' => DonationStatus::Succeeded,
            ]);

            $campaign = $donation->campaign;
            $previousCollected = (float) $campaign->collected_amount;
            $campaign->increment('collected_amount', (float) ($donation->base_amount ?? $donation->gross_amount));
            $campaign->refresh();

            SendCampaignMilestoneNotification::dispatch($campaign, $previousCollected);

            if ($donation->type === DonationType::Recurring) {
                $subscription = app(CreateRecurringSubscription::class)->create($donation, $paymentIntent, $stripeOptions);
                $donation->update(['subscription_id' => $subscription->getKey()]);
                $subscription->increment('payment_count');
            }

            SendDonationReceipt::dispatch($donation);

            if ($donation->type !== DonationType::Recurring) {
                SendNewDonationNotification::dispatch($donation);
            }

            SendLargeDonationNotification::dispatch($donation);
            SendMetaConversionEvent::dispatch($donation);
            SyncDonationStripeDetailsJob::dispatch($donation->getKey())->delay(now()->addMinutes(2));
        } catch (\Exception $e) {
            // Log error silently
        }
    }

    /**
     * @return array<string, string>
     */
    private function stripeOptionsFor(Donation $donation): array
    {
        $organization = $donation->campaign?->organization;

        if (! $organization?->stripe_account_id || ! $organization->stripe_onboarded) {
            return [];
        }

        return ['stripe_account' => $organization->stripe_account_id];
    }

    #[Renderless]
    public function submit(): string
    {
        $validated = $this->validate();
        $email = str($validated['email'])->lower()->toString();

        $donor = Donor::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?? null,
            ],
        );

        $campaignId = $this->element?->campaign_id ?? $this->campaign?->getKey();

        $pageQuery = [];
        if (filled($this->pageUrl)) {
            $parsed = parse_url($this->pageUrl);
            parse_str($parsed['query'] ?? '', $pageQuery);
        }

        $utmParams = [
            'frequency' => $validated['frequency'],
            'dedicate' => (bool) ($validated['dedicate'] ?? false),
            'source' => $this->element ? 'element' : 'direct',
            'utm_source' => $pageQuery['utm_source'] ?? null,
            'utm_medium' => $pageQuery['utm_medium'] ?? null,
            'utm_campaign' => $pageQuery['utm_campaign'] ?? null,
            'utm_content' => $pageQuery['utm_content'] ?? null,
            'utm_term' => $pageQuery['utm_term'] ?? null,
            'fbclid' => $pageQuery['fbclid'] ?? null,
            'gclid' => $pageQuery['gclid'] ?? null,
            'ttclid' => $pageQuery['ttclid'] ?? null,
            'referrer' => request()->header('referer'),
        ];

        if ($this->element) {
            $utmParams['element_id'] = $this->element->getKey();
            $utmParams['element_token'] = $this->element->token;
            $utmParams['element_type'] = $this->element->type->value;
            $utmParams['element_name'] = $this->element->name;
        }

        $clientInfo = [
            ...ClientInfo::fromRequest(request()),
            'page_url' => $this->pageUrl,
        ];

        $fraudService = new FraudDetectionService($donor);
        $fraudResult = $fraudService->assess([
            'amount' => $validated['amount'],
            'billing_country' => null, // captured after Stripe
        ]);

        if ($fraudResult['action'] === 'block') {
            FraudDetectionService::logAttempt([
                'donor_id' => $donor->getKey(),
                'email' => $email,
                'amount' => $validated['amount'],
                'currency' => $this->currency,
                'reason' => $fraudResult['matches'][0]['reason'] ?? 'Unknown',
                'action' => 'blocked',
                'metadata' => $fraudResult['matches'],
            ]);

            throw new \RuntimeException('This transaction has been blocked for security review. Please contact support.');
        }

        $fraudStatus = match ($fraudResult['action']) {
            'flag' => 'flagged',
            default => 'clean',
        };

        $donation = Donation::query()->create([
            'campaign_id' => $campaignId,
            'donor_id' => $donor->getKey(),
            'gross_amount' => $validated['amount'],
            'stripe_fee' => 0,
            'donor_fee_covered' => $this->estimatedFee,
            'processing_fee' => 0,
            'net_amount' => $validated['amount'],
            'currency' => $this->currency,
            'status' => DonationStatus::Pending,
            'type' => $validated['frequency'] === 'monthly' ? DonationType::Recurring : DonationType::OneTime,
            'donor_message' => filled($validated['comment'] ?? null) ? $validated['comment'] : null,
            'is_anonymous' => false,
            'fraud_status' => $fraudStatus,
            'utm_params' => $utmParams,
            ...$clientInfo,
        ]);

        $this->donationPublicId = $donation->public_id;

        // Send fraud notifications for flagged donations (blocked donations throw before this)
        if ($fraudStatus === 'flagged') {
            FraudDetectionService::notifyAdmins(
                $donation,
                $fraudResult['matches'][0]['reason'] ?? 'Flagged by fraud rules',
                'flagged'
            );
        }

        try {
            $paymentIntent = app(CreatePaymentIntent::class)->create($donation);
            $donation->update(['stripe_payment_intent_id' => $paymentIntent->id]);

            return $paymentIntent->client_secret;
        } catch (\Exception $e) {
            $donation->update(['status' => DonationStatus::Failed]);

            throw $e;
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:'.($this->campaign?->minimum_amount ?? $this->element?->campaign?->minimum_amount ?? 1), 'max:100000'],
            'currency' => ['required', 'string', 'in:myr,usd,sgd'],
            'frequency' => [
                'required',
                Rule::in($this->config('allow_monthly', true) ? ['one_time', 'monthly'] : ['one_time']),
            ],
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'coverFee' => ['boolean'],
            'dedicate' => ['boolean'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<int, int>
     */
    public function suggestedAmounts(?string $frequency = null): array
    {
        $frequency ??= $this->frequency;

        $campaign = $this->element?->campaign ?? $this->campaign;

        if (! $campaign) {
            return [];
        }

        $amounts = $campaign->suggested_amounts;

        // New per-currency format: {myr: {one_time: [...], monthly: [...]}}
        if (is_array($amounts) && isset($amounts[$this->currency])) {
            $currencyAmounts = $amounts[$this->currency];
            if (is_array($currencyAmounts) && isset($currencyAmounts[$frequency])) {
                $amounts = $currencyAmounts[$frequency];
            } else {
                $amounts = [];
            }
        } elseif (is_array($amounts) && isset($amounts[$frequency])) {
            // Old format {one_time: [...], monthly: [...]} — backward compat
            $amounts = $amounts[$frequency];
        } else {
            $amounts = $campaign->{'suggested_amounts_'.$frequency};
        }

        if (! is_array($amounts) || $amounts === []) {
            $amounts = $this->config('suggested_amounts_'.$frequency);
        }

        if (! is_array($amounts) || $amounts === []) {
            $amounts = $this->config('suggested_amounts');
        }

        if (! is_array($amounts) || $amounts === []) {
            $amounts = [200, 100, 50, 30, 10, 5];
        }

        return collect($amounts)
            ->map(fn (mixed $amount): int => (int) (is_array($amount) ? ($amount['amount'] ?? $amount['value'] ?? 0) : $amount))
            ->filter(fn (int $amount): bool => $amount > 0)
            ->unique()
            ->values()
            ->all();
    }

    public function config(string $key, mixed $default = null): mixed
    {
        $sentinel = new \stdClass;

        if ($this->element) {
            $value = data_get($this->element->config ?? [], $key, $sentinel);
            if ($value !== $sentinel) {
                return $value;
            }
        }

        $campaign = $this->campaign ?? $this->element?->campaign;

        if ($campaign) {
            return data_get($campaign->config ?? [], $key, $default);
        }

        return $default;
    }

    #[Computed]
    public function estimatedFee(): float
    {
        if (! $this->coverFee || ! $this->config('allow_cover_fee', true)) {
            return 0.0;
        }

        $fixedFees = ['myr' => 0.50, 'usd' => 0.30, 'sgd' => 0.50];
        $fixedFee = $fixedFees[$this->currency] ?? 0.50;

        return round((float) $this->amount * 0.03 + $fixedFee, 2);
    }

    public function render()
    {
        if ($this->element !== null && $this->element->campaign !== null) {
            $this->element->campaign = Campaign::query()->find($this->element->campaign->getKey());
        }

        if ($this->campaign !== null) {
            $this->campaign = Campaign::query()->find($this->campaign->getKey());
        }

        $organization = $this->element?->campaign?->organization ?? $this->campaign?->organization;
        $trackingConfigs = $organization ? TrackingScriptService::config($organization) : [];

        return view('livewire.donation-form')
            ->layout($this->isEmbed ? 'layouts.embed' : ($this->isPopup ? 'layouts.popup' : 'layouts.donation'), [
                'organization' => $organization,
                'trackingConfigs' => $trackingConfigs,
            ]);
    }
}
