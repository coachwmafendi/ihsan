<?php

namespace App\Livewire;

use App\Actions\Chip\CreatePurchase;
use App\Actions\Chip\FinalizeDonation;
use App\Actions\Stripe\CreatePaymentIntent;
use App\Actions\Stripe\SyncDonationStripeDetails;
use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\DonationType;
use App\Enums\ElementType;
use App\Enums\PaymentGateway;
use App\Jobs\SendCampaignMilestoneNotification;
use App\Jobs\SendDonationReceipt;
use App\Jobs\SendDonorNewSubscriptionNotification;
use App\Jobs\SendGa4TrackingEvent;
use App\Jobs\SendLargeDonationNotification;
use App\Jobs\SendLinkedInConversionEvent;
use App\Jobs\SendMetaConversionEvent;
use App\Jobs\SendMetaTrackingEvent;
use App\Jobs\SendNewDonationNotification;
use App\Jobs\SendNewSubscriptionNotification;
use App\Jobs\SendSnapchatConversionEvent;
use App\Jobs\SendXAdsConversionEvent;
use App\Jobs\SyncDonationStripeDetailsJob;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Donor;
use App\Models\Element;
use App\Models\Subscription;
use App\Services\DonationFeeEstimator;
use App\Services\FraudDetectionService;
use App\Services\RecurringPlanResolver;
use App\Services\TrackingScriptService;
use App\Support\ChipFpxBanks;
use App\Support\ClientInfo;
use App\Support\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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

    public string $firstName = '';

    public string $lastName = '';

    public string $email = '';

    public string $phone = '';

    public bool $dedicate = false;

    public string $comment = '';

    public bool $isEmbed = false;

    public bool $isPopup = false;

    public bool $isPublicPage = false;

    public string $pageUrl = '';

    public string $currency = 'myr';

    public bool $coverFee = true;

    public ?string $donationPublicId = null;

    public ?string $chipErrorMessage = null;

    public ?string $chipDirectPostUrl = null;

    public string $chipPaymentMethod = 'card';

    public ?string $chipFpxBankCode = null;

    public float $campaignCollectedAmount = 0.0;

    public float $campaignTargetAmount = 0.0;

    /**
     * @return array<int, string>
     */
    public function getAcceptedCurrencies(): array
    {
        if ($this->isChipGateway()) {
            return ['myr'];
        }

        $organization = $this->element?->campaign?->organization ?? $this->campaign?->organization;

        if ($organization === null) {
            return ['myr'];
        }

        return $organization->settings['accepted_currencies'] ?? ['myr'];
    }

    private function isChipGateway(): bool
    {
        $campaign = $this->element?->campaign ?? $this->campaign;

        return $campaign?->payment_gateway?->value === 'chip';
    }

    public function useChipDirectPost(): bool
    {
        $campaign = $this->element?->campaign ?? $this->campaign;

        if ($campaign?->payment_gateway !== PaymentGateway::Chip) {
            return false;
        }

        $methods = $campaign->organization->chipPaymentMethods();

        return count($methods) === 1 && in_array('card', $methods, true);
    }

    /**
     * @return array<int, string>
     */
    public function chipPaymentMethods(): array
    {
        $campaign = $this->element?->campaign ?? $this->campaign;

        if ($campaign?->payment_gateway !== PaymentGateway::Chip) {
            return [];
        }

        $methods = $campaign->organization->chipPaymentMethods();

        // Recurring CHIP donations can only be charged to cards.
        if ($this->frequency === 'monthly') {
            return array_values(array_filter($methods, fn (string $method): bool => $method === 'card'));
        }

        return $methods;
    }

    public function selectCurrency(string $currency, bool $resetAmount = true): void
    {
        if ($this->isChipGateway()) {
            $this->currency = 'myr';

            return;
        }

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
                ! $element->is_active
                    || $element->campaign === null
                    || $element->campaign->status !== CampaignStatus::Active,
                404
            );

            $this->element = $element->loadMissing(['campaign.organization']);
            $this->amount = $this->config('default_amount', $this->suggestedAmounts()[0] ?? 5);
            $this->frequency = $this->config('default_frequency', $this->config('allow_monthly', true) ? 'monthly' : 'one_time');
            $this->setElementPresentationMode($element);
        } elseif ($campaign instanceof Campaign) {
            abort_if(
                $campaign->status !== CampaignStatus::Active || (! $this->isPublicPage && ! $campaign->checkout_modal_enabled),
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
                ! $element->is_active
                    || $element->campaign === null
                    || $element->campaign->status !== CampaignStatus::Active,
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
                $campaign->status !== CampaignStatus::Active || (! $this->isPublicPage && ! $campaign->checkout_modal_enabled),
                404
            );

            $this->campaign = $campaign->loadMissing(['organization']);
            $this->amount = $this->suggestedAmounts()[0] ?? 5;
            $this->frequency = $this->config('allow_monthly', true) ? 'monthly' : 'one_time';
            $this->setCampaignPresentationMode();
        } else {
            abort(404);
        }

        $this->syncCampaignTotals();
        $this->overrideFromQueryParams();
        $this->handleChipReturnQueryParams();

        if ($this->isChipGateway()) {
            $this->currency = 'myr';
        }
    }

    private function handleChipReturnQueryParams(): void
    {
        $chipStatus = request()->query('chip_status');
        $chipDonationId = request()->query('donation_id');

        if (! in_array($chipStatus, ['success', 'failure', 'cancelled', 'cancel'], true) || blank($chipDonationId)) {
            return;
        }

        $this->donationPublicId = is_string($chipDonationId) ? $chipDonationId : null;

        $this->dispatch('chip-return', status: $chipStatus, donationId: $this->donationPublicId);
    }

    private function syncCampaignTotals(): void
    {
        $campaign = $this->element?->campaign ?? $this->campaign;

        if ($campaign === null) {
            return;
        }

        $this->campaignCollectedAmount = (float) $campaign->collected_amount;
        $this->campaignTargetAmount = (float) ($campaign->target_amount ?? 0);
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

        $donation = Donation::query()
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();

        if ($donation === null) {
            return;
        }

        $campaignPublicId = Campaign::query()->whereKey($donation->campaign_id)->value('public_id');

        if ($campaignPublicId !== null) {
            $this->dispatch('campaign-donation-received', campaignPublicId: $campaignPublicId);
        }

        if ($donation->status === DonationStatus::Succeeded) {
            return;
        }

        try {
            Stripe::setApiKey(config('services.stripe.secret'));
            $donation->loadMissing('campaign.organization');
            $stripeOptions = $this->stripeOptionsFor($donation);
            $synced = app(SyncDonationStripeDetails::class)->sync($donation, $paymentIntent, $stripeOptions);
            $paymentIntent = $synced['payment_intent'];

            $finalizeResult = DB::transaction(function () use ($donation): array {
                $lockedDonation = Donation::query()->whereKey($donation->getKey())->lockForUpdate()->firstOrFail();

                if ($lockedDonation->status === DonationStatus::Succeeded) {
                    return ['wasAlreadySucceeded' => true];
                }

                $lockedDonation->update([
                    'status' => DonationStatus::Succeeded,
                ]);

                $campaign = Campaign::query()->whereKey($lockedDonation->campaign_id)->lockForUpdate()->first();

                if ($campaign === null) {
                    return ['wasAlreadySucceeded' => false, 'campaign' => null, 'previousCollected' => 0];
                }

                $previousCollected = (float) $campaign->collected_amount;
                $campaign->increment('collected_amount', (float) ($lockedDonation->base_amount ?? $lockedDonation->gross_amount));

                return ['wasAlreadySucceeded' => false, 'campaign' => $campaign, 'previousCollected' => $previousCollected];
            });

            if ($finalizeResult['wasAlreadySucceeded']) {
                return;
            }

            $campaign = $finalizeResult['campaign'];

            if ($campaign === null) {
                return;
            }

            $previousCollected = $finalizeResult['previousCollected'];
            $campaign->refresh();
            $this->campaignCollectedAmount = (float) $campaign->collected_amount;
            $this->campaignTargetAmount = (float) ($campaign->target_amount ?? 0);
            $this->donationPublicId = $donation->public_id;
            $this->syncDonorDetails($donation);

            SendCampaignMilestoneNotification::dispatch($campaign, $previousCollected);

            $isNewSubscription = $donation->type === DonationType::Recurring
                && $donation->fresh()?->subscription_id === null;

            if ($isNewSubscription) {
                $subscription = $this->createRecurringPlan($donation, $paymentIntent, $stripeOptions);

                if ($subscription->wasRecentlyCreated) {
                    SendNewSubscriptionNotification::dispatch($donation);
                    SendDonorNewSubscriptionNotification::dispatch($donation);
                }
            }

            if (! $isNewSubscription) {
                SendDonationReceipt::dispatch($donation);

                if ($donation->type !== DonationType::Recurring) {
                    SendNewDonationNotification::dispatch($donation);
                }

                SendLargeDonationNotification::dispatch($donation);
                SendMetaConversionEvent::dispatch($donation);
                SendLinkedInConversionEvent::dispatch($donation);
                SendXAdsConversionEvent::dispatch($donation);
                SendSnapchatConversionEvent::dispatch($donation);
            }

            SyncDonationStripeDetailsJob::dispatch($donation->getKey())->delay(now()->addMinutes(2));
        } catch (\Exception $e) {
            report($e);
        }
    }

    #[Renderless]
    public function confirmChipPayment(string $donationPublicId): void
    {
        $this->skipRender();

        $donation = Donation::query()->where('public_id', $donationPublicId)->first();

        if ($donation === null) {
            return;
        }

        $campaignId = $this->element?->campaign_id ?? $this->campaign?->getKey();

        if ($donation->campaign_id !== $campaignId) {
            return;
        }

        try {
            app(FinalizeDonation::class)->finalize($donation);
            $this->syncCampaignTotals();
            $this->syncDonorDetails($donation);
        } catch (\Exception $e) {
            report($e);
        }
    }

    /**
     * Poll-driven finalizer used by the direct-post iframe flow. Returns true
     * only when the donation has actually been finalized successfully.
     */
    #[Renderless]
    public function pollChipPaymentStatus(string $donationPublicId): bool
    {
        $this->skipRender();

        $donation = Donation::query()->where('public_id', $donationPublicId)->first();

        if ($donation === null) {
            return false;
        }

        $campaignId = $this->element?->campaign_id ?? $this->campaign?->getKey();

        if ($donation->campaign_id !== $campaignId) {
            return false;
        }

        try {
            app(FinalizeDonation::class)->finalize($donation);
            $this->syncCampaignTotals();
            $this->syncDonorDetails($donation);

            return true;
        } catch (\Exception $e) {
            report($e);

            return false;
        }
    }

    private function syncDonorDetails(Donation $donation): void
    {
        $donor = $donation->donor;

        if ($donor === null) {
            return;
        }

        $this->firstName = $donor->first_name ?? '';
        $this->lastName = $donor->last_name ?? '';
        $this->email = $donor->email ?? '';
        $this->phone = $donor->phone ?? '';
    }

    /**
     * @return array<string, string>
     */
    private function stripeOptionsFor(Donation $donation): array
    {
        $organization = $donation->campaign?->organization;

        if (! $organization?->stripe_account_id || ! $organization->stripe_active) {
            return [];
        }

        return ['stripe_account' => $organization->stripe_account_id];
    }

    /**
     * @param  array<string, string>  $stripeOptions
     */
    private function createRecurringPlan(Donation $donation, StripePaymentIntent $paymentIntent, array $stripeOptions): Subscription
    {
        return app(RecurringPlanResolver::class)->create($donation, $paymentIntent, $stripeOptions);
    }

    #[Renderless]
    public function submit(): string
    {
        // CHIP recurring donations can only be charged to cards.
        if ($this->frequency === 'monthly' && $this->chipPaymentMethod === 'fpx') {
            $this->chipPaymentMethod = 'card';
            $this->chipFpxBankCode = null;
        }

        $validated = $this->validate();
        $email = str($validated['email'])->lower()->toString();

        $donor = Donor::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $validated['firstName'],
                'last_name' => $validated['lastName'] ?? null,
                'phone' => $validated['phone'] ?? null,
            ],
        );

        $campaignId = $this->element?->campaign_id ?? $this->campaign?->getKey();

        $pageQuery = [];
        if (filled($this->pageUrl)) {
            $parsed = parse_url($this->pageUrl);
            parse_str($parsed['query'] ?? '', $pageQuery);
        }

        $source = $this->element ? 'element' : ($this->isPublicPage ? 'campaign_page' : 'checkout_modal');

        $utmParams = [
            'frequency' => $validated['frequency'],
            'dedicate' => (bool) ($validated['dedicate'] ?? false),
            'source' => $source,
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
            'source' => $source,
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

        $campaign = $this->element?->campaign ?? $this->campaign;
        $isChip = $campaign !== null && $campaign->payment_gateway === PaymentGateway::Chip;

        if ($isChip) {
            if (! $campaign->organization?->chip_active) {
                $donation->update(['status' => DonationStatus::Failed]);
                $this->chipErrorMessage = 'CHIP is not configured for this organization. Please choose another payment method or contact support.';

                return '';
            }

            $returnTo = $this->isPublicPage && $this->campaign !== null
                ? route('campaigns.public', $this->campaign)
                : $this->pageUrl;

            try {
                if ($this->useChipDirectPost()) {
                    $this->chipDirectPostUrl = app(CreatePurchase::class)->createDirectPost($donation, $returnTo);

                    return '';
                }

                return app(CreatePurchase::class)->create(
                    $donation,
                    $returnTo,
                    $this->chipPaymentMethod,
                    $this->chipFpxBankCode,
                );
            } catch (\Exception $e) {
                $donation->update(['status' => DonationStatus::Failed]);
                $this->chipErrorMessage = 'Unable to start CHIP payment. Please try again.';

                report($e);

                return '';
            }
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
    private function buildTrackingContext(): array
    {
        $campaign = $this->element?->campaign ?? $this->campaign;
        $organization = $campaign?->organization;

        if (! $organization) {
            return [];
        }

        $pageQuery = [];
        if (filled($this->pageUrl)) {
            $parsed = parse_url($this->pageUrl);
            parse_str($parsed['query'] ?? '', $pageQuery);
        }

        $userData = [
            'client_ip_address' => request()->ip(),
            'client_user_agent' => request()->userAgent(),
            'external_id' => hash('sha256', session()->getId() ?: Str::uuid()->toString()),
        ];

        if (filled($this->email)) {
            $userData['em'] = hash('sha256', strtolower(trim($this->email)));
        }

        if (filled($pageQuery['fbclid'] ?? null)) {
            $userData['fbc'] = 'fb.1.'.time().'.'.$pageQuery['fbclid'];
        }

        return [
            'organization_id' => $organization->id,
            'event_source_url' => $this->pageUrl ?: request()->fullUrl(),
            'campaign_name' => $campaign?->title,
            'user_data' => array_filter($userData),
        ];
    }

    #[Renderless]
    public function trackServerPageView(): void
    {
        if (session()->get('ihsan.tracking.pageview.sent')) {
            return;
        }

        $context = $this->buildTrackingContext();
        if (empty($context)) {
            return;
        }

        SendMetaTrackingEvent::dispatch(
            $context['organization_id'],
            'PageView',
            $context['event_source_url'],
            $context['user_data'],
            [],
            $context['campaign_name'],
        );

        SendGa4TrackingEvent::dispatch(
            $context['organization_id'],
            'page_view',
            $context['user_data']['external_id'] ?? (string) Str::uuid(),
            $context['event_source_url'],
            [],
            $context['campaign_name'],
        );

        session()->put('ihsan.tracking.pageview.sent', true);
    }

    #[Renderless]
    public function trackServerInitiateCheckout(): void
    {
        if (session()->get('ihsan.tracking.initiate.sent')) {
            return;
        }

        $context = $this->buildTrackingContext();
        if (empty($context)) {
            return;
        }

        $amount = (float) $this->amount;

        if ($amount <= 0) {
            return;
        }

        $currency = strtoupper($this->currency);

        $metaCustomData = [
            'value' => $amount,
            'currency' => $currency,
            'content_type' => 'product',
            'contents' => [[
                'id' => 'donation',
                'quantity' => 1,
                'item_price' => $amount,
            ]],
        ];

        SendMetaTrackingEvent::dispatch(
            $context['organization_id'],
            'InitiateCheckout',
            $context['event_source_url'],
            $context['user_data'],
            $metaCustomData,
            $context['campaign_name'],
        );

        SendGa4TrackingEvent::dispatch(
            $context['organization_id'],
            'begin_checkout',
            $context['user_data']['external_id'] ?? (string) Str::uuid(),
            $context['event_source_url'],
            [
                'value' => $amount,
                'currency' => $currency,
            ],
            $context['campaign_name'],
        );

        session()->put('ihsan.tracking.initiate.sent', true);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $rules = [
            'amount' => ['required', 'numeric', 'min:'.($this->campaign?->minimum_amount ?? $this->element?->campaign?->minimum_amount ?? 1), 'max:100000'],
            'currency' => ['required', 'string', 'in:myr,usd,sgd'],
            'frequency' => [
                'required',
                Rule::in($this->config('allow_monthly', true) ? ['one_time', 'monthly'] : ['one_time']),
            ],
            'firstName' => ['required', 'string', 'max:120'],
            'lastName' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'coverFee' => ['boolean'],
            'dedicate' => ['boolean'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];

        $chipMethods = $this->chipPaymentMethods();

        if ($chipMethods !== []) {
            $rules['chipPaymentMethod'] = ['required', Rule::in($chipMethods)];

            if (in_array('fpx', $chipMethods, true)) {
                $rules['chipFpxBankCode'] = [
                    'required_if:chipPaymentMethod,fpx',
                    'nullable',
                    'string',
                    Rule::in(array_keys(ChipFpxBanks::b2cMap())),
                ];
            }
        }

        return $rules;
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

        $campaign = $this->element?->campaign ?? $this->campaign;
        $gateway = $campaign?->payment_gateway?->value ?? 'stripe';

        return DonationFeeEstimator::estimate(
            (float) $this->amount,
            $this->currency,
            $gateway
        );
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
