<?php

declare(strict_types=1);

namespace App\Livewire\App\Campaigns;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Enums\PaymentGateway;
use App\Models\Campaign;
use App\Models\Donation;
use App\Rules\UniqueNameWithinOrganization;
use App\Services\MonthlyUpsellRules;
use App\Services\MonthlyUpsellStats;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Edit Campaign')]
class CampaignEdit extends Component
{
    use WithFileUploads;

    private const MaxAmount = 99999;

    private const MaxTargetAmount = 9999999;

    private const MaxUpsellTiers = 6;

    #[Locked]
    public Campaign $campaign;

    public string $activeTab = 'overview';

    public bool $showArchiveModal = false;

    public string $suggestedActiveFreq = 'one_time';

    public string $checkoutPanel = 'currency';

    /** Window for the monthly upsell results: '30', '90' or 'all'. */
    public string $upsellStatsPeriod = 'all';

    /** @var string[] */
    public array $acceptedCurrencies = ['MYR'];

    public string $activeCurrency = 'MYR';

    public string $default_currency = 'MYR';

    public bool $currency_autodetect = false;

    /** @var array<string, array<string, array<int, array{value: float, label: string}>>> */
    public array $allSuggestedAmounts = [];

    /** @var array<int, array{value: float, label: string}> */
    public array $suggestedOneTime = [];

    /** @var array<int, array{value: float, label: string}> */
    public array $suggestedMonthly = [];

    public string $title = '';

    #[Validate('required|string|in:active,draft,paused,ended,archived')]
    public string $status = 'draft';

    #[Validate('nullable|string|max:5000')]
    public ?string $description = null;

    public ?string $existing_image = null;

    #[Validate('nullable|image|max:5120')]
    public $image = null;

    public bool $has_target = false;

    #[Validate('nullable|integer|min:0|max:9999999')]
    public ?string $target_amount = null;

    public bool $has_end_date = false;

    #[Validate('nullable|date')]
    public ?string $end_date = null;

    public bool $allow_recurring = true;

    public bool $upsell_enabled = false;

    public int $upsell_cooldown_days = 30;

    public ?string $upsell_heading = null;

    public ?string $upsell_body = null;

    public ?string $upsell_decline_label = null;

    /** @var array<int, array{min: float, max: float|null, offers: array<int, array{type: string, value: float}>}> */
    public array $upsell_tiers = [];

    public bool $allow_custom_amount = true;

    public bool $allow_cover_fee = true;

    public bool $show_comment = true;

    public bool $show_phone = true;

    #[Validate('nullable|integer|min:0|max:99999')]
    public ?string $minimum_amount = null;

    public string $default_frequency = 'one_time';

    #[Validate('nullable|integer|min:0|max:99999')]
    public ?string $default_amount = null;

    public ?string $newOneTimeValue = null;

    public ?string $newMonthlyValue = null;

    #[Validate('nullable|string|max:5000')]
    public ?string $thank_you_message = null;

    #[Validate('nullable|url|max:500')]
    public ?string $redirect_url = null;

    public string $campaignPagePanel = 'content';

    public bool $show_total_raised = true;

    #[Validate('nullable|string|in:stripe,chip')]
    public string $payment_gateway = 'stripe';

    public bool $campaign_page_enabled = true;

    public string $postDonationMode = 'default';

    /** @var string[] */
    public array $shareChannels = ['facebook', 'x', 'linkedin', 'email'];

    #[Validate('nullable|string|max:280')]
    public ?string $shareMessage = null;

    public ?string $existingContentLogo = null;

    #[Validate('nullable|image|max:5120')]
    public $contentLogo = null;

    public ?string $existingContentImage = null;

    #[Validate('nullable|image|max:5120')]
    public $contentImage = null;

    #[Validate('nullable|string|max:255')]
    public ?string $contentTitle = null;

    public ?string $contentMessage = null;

    public function mount(Campaign $campaign): void
    {
        $this->authorize('update', $campaign);

        $this->campaign = $campaign;
        $this->title = $campaign->title;
        $this->status = $campaign->status->value;
        $this->description = $campaign->description;
        $this->existing_image = $campaign->image_path;
        $this->has_target = $campaign->has_target ?? false;
        $this->target_amount = $this->sanitizeOptionalAmount($campaign->target_amount, self::MaxTargetAmount);
        $this->has_end_date = $campaign->has_end_date ?? false;
        $this->end_date = $campaign->end_date?->format('Y-m-d');
        $this->allow_recurring = $campaign->allow_recurring ?? false;
        $this->hydrateMonthlyUpsell($campaign);
        $this->allow_custom_amount = $campaign->allow_custom_amount ?? false;
        $this->allow_cover_fee = $campaign->config['allow_cover_fee'] ?? true;
        $this->minimum_amount = $this->sanitizeOptionalAmount($campaign->minimum_amount);
        $this->thank_you_message = $campaign->thank_you_message;
        $this->redirect_url = $campaign->redirect_url;
        $this->postDonationMode = $campaign->config['post_donation_mode'] ?? 'default';
        $this->shareChannels = $campaign->config['share_channels'] ?? ['facebook', 'x', 'linkedin', 'email'];
        $this->shareMessage = $campaign->config['share_message'] ?? 'Join me in supporting this meaningful cause. Every contribution, big or small, can make a real difference. Your kindness matters.';

        $this->show_total_raised = $campaign->config['show_total_raised'] ?? true;
        $this->campaign_page_enabled = $campaign->campaign_page_enabled ?? true;
        $this->payment_gateway = $campaign->payment_gateway?->value ?? PaymentGateway::Stripe->value;

        $this->existingContentLogo = $campaign->config['content_logo'] ?? null;
        $this->existingContentImage = $campaign->config['content_image'] ?? null;
        $this->contentTitle = $campaign->config['content_title'] ?? $campaign->title;
        $this->contentMessage = $campaign->config['content_message'] ?? $campaign->description;

        $org = Auth::user()?->organization;

        // Load accepted currencies from org settings
        $rawAccepted = $org?->settings['accepted_currencies'] ?? [];
        $this->acceptedCurrencies = array_map('strtoupper', $rawAccepted);
        if (empty($this->acceptedCurrencies)) {
            $this->acceptedCurrencies = ['MYR'];
        }
        $this->activeCurrency = $this->acceptedCurrencies[0];

        // Load nested per-currency amounts from config or legacy columns
        $this->allSuggestedAmounts = $campaign->config['suggested_amounts_by_currency'] ?? [];

        // If no nested data exists, attempt to migrate from legacy columns
        if (empty($this->allSuggestedAmounts)) {
            $legacyOneTime = $this->hydrateSuggested($campaign->suggested_amounts_one_time ?? [], []);
            $legacyMonthly = $this->hydrateSuggested($campaign->suggested_amounts_monthly ?? [], []);
            $legacyGeneral = $this->hydrateSuggested($campaign->suggested_amounts ?? [], []);

            if (! empty($legacyOneTime) || ! empty($legacyMonthly)) {
                $this->allSuggestedAmounts[$this->activeCurrency] = [
                    'one_time' => $legacyOneTime,
                    'monthly' => $legacyMonthly,
                ];
            } elseif (! empty($legacyGeneral)) {
                $this->allSuggestedAmounts[$this->activeCurrency] = [
                    'one_time' => $legacyGeneral,
                    'monthly' => [],
                ];
            }
        }

        // Ensure every accepted currency has at least default amounts
        foreach ($this->acceptedCurrencies as $currency) {
            if (! isset($this->allSuggestedAmounts[$currency])) {
                $this->allSuggestedAmounts[$currency] = $this->defaultAmountsForCurrency($currency);
            }
        }

        $this->syncActiveCurrencyAmounts();

        $this->default_frequency = $campaign->config['default_frequency'] ?? 'one_time';
        $this->default_amount = $this->sanitizeOptionalAmount($campaign->config['default_amount'] ?? 50);
        $this->default_currency = $campaign->config['default_currency'] ?? $this->acceptedCurrencies[0];
        $this->currency_autodetect = $campaign->config['currency_autodetect'] ?? false;
        $this->show_comment = $campaign->config['show_comment'] ?? true;
        $this->show_phone = $campaign->config['show_phone'] ?? true;
    }

    public function updatedActiveCurrency(): void
    {
        // Save current amounts back into the nested array before switching
        $this->allSuggestedAmounts[$this->activeCurrency] = [
            'one_time' => $this->suggestedOneTime,
            'monthly' => $this->suggestedMonthly,
        ];

        $this->syncActiveCurrencyAmounts();
    }

    public function updatedSuggestedActiveFreq(): void
    {
        // Frequency tab changed — amounts already in sync, no-op
    }

    private function syncActiveCurrencyAmounts(): void
    {
        $defaults = $this->defaultAmountsForCurrency($this->activeCurrency);
        $data = $this->allSuggestedAmounts[$this->activeCurrency] ?? $defaults;
        $this->suggestedOneTime = $this->backfillSuggestedDefaults($data['one_time'] ?? [], $defaults['one_time']);
        $this->suggestedMonthly = $this->backfillSuggestedDefaults($data['monthly'] ?? [], $defaults['monthly']);
    }

    /** @return array<string, array<int, array{value: float, label: string}>> */
    private function defaultAmountsForCurrency(string $currency): array
    {
        return match ($currency) {
            'USD' => [
                'one_time' => [
                    ['value' => 100, 'label' => ''],
                    ['value' => 75, 'label' => ''],
                    ['value' => 50, 'label' => ''],
                    ['value' => 25, 'label' => ''],
                    ['value' => 10, 'label' => ''],
                    ['value' => 5, 'label' => ''],
                ],
                'monthly' => [
                    ['value' => 50, 'label' => ''],
                    ['value' => 30, 'label' => ''],
                    ['value' => 20, 'label' => ''],
                    ['value' => 15, 'label' => ''],
                    ['value' => 10, 'label' => ''],
                    ['value' => 5, 'label' => ''],
                ],
            ],
            'SGD' => [
                'one_time' => [
                    ['value' => 150, 'label' => ''],
                    ['value' => 100, 'label' => ''],
                    ['value' => 75, 'label' => ''],
                    ['value' => 50, 'label' => ''],
                    ['value' => 25, 'label' => ''],
                    ['value' => 10, 'label' => ''],
                ],
                'monthly' => [
                    ['value' => 75, 'label' => ''],
                    ['value' => 50, 'label' => ''],
                    ['value' => 30, 'label' => ''],
                    ['value' => 20, 'label' => ''],
                    ['value' => 10, 'label' => ''],
                    ['value' => 5, 'label' => ''],
                ],
            ],
            default => [ // MYR
                'one_time' => [
                    ['value' => 500, 'label' => ''],
                    ['value' => 400, 'label' => ''],
                    ['value' => 300, 'label' => ''],
                    ['value' => 200, 'label' => ''],
                    ['value' => 100, 'label' => ''],
                    ['value' => 50, 'label' => ''],
                ],
                'monthly' => [
                    ['value' => 300, 'label' => ''],
                    ['value' => 200, 'label' => ''],
                    ['value' => 150, 'label' => ''],
                    ['value' => 100, 'label' => ''],
                    ['value' => 50, 'label' => ''],
                    ['value' => 30, 'label' => ''],
                ],
            ],
        };
    }

    /** @param array<int, mixed>|null $stored */
    private function hydrateSuggested(?array $stored, array $defaults): array
    {
        if (empty($stored)) {
            return $defaults;
        }

        return collect($stored)->map(function ($item) {
            if (is_array($item)) {
                $val = $this->amountToInteger($item['value'] ?? 0);

                return ['value' => max(1, min(self::MaxAmount, $val)), 'label' => $item['label'] ?? ''];
            }

            $val = $this->amountToInteger($item);

            return ['value' => max(1, min(self::MaxAmount, $val)), 'label' => ''];
        })->values()->toArray();
    }

    public function updated(string $property, mixed $value): void
    {
        if ($property === 'target_amount') {
            data_set($this, $property, $this->sanitizeOptionalAmount($value, self::MaxTargetAmount));

            return;
        }

        if (in_array($property, ['minimum_amount', 'default_amount', 'newOneTimeValue', 'newMonthlyValue'], true)) {
            data_set($this, $property, $this->sanitizeOptionalAmount($value));

            return;
        }

        // Clamp suggested amount values to 1–99999 when inputs blur
        if (str_starts_with($property, 'suggestedOneTime.') || str_starts_with($property, 'suggestedMonthly.')) {
            data_set($this, $property, $this->sanitizeSuggestedAmount($value));
        }
    }

    private function sanitizeOptionalAmount(mixed $value, int $max = self::MaxAmount): ?string
    {
        $digits = $this->digitsBeforeDecimalSeparator($value, strlen((string) $max));

        if ($digits === '') {
            return null;
        }

        return (string) min($max, (int) $digits);
    }

    private function sanitizeSuggestedAmount(mixed $value): int
    {
        $digits = $this->digitsBeforeDecimalSeparator($value, strlen((string) self::MaxAmount));

        if ($digits === '' || ((int) $digits) <= 0) {
            return 0;
        }

        return max(1, min(self::MaxAmount, (int) $digits));
    }

    private function amountToInteger(mixed $value): int
    {
        $digits = $this->digitsBeforeDecimalSeparator($value, strlen((string) self::MaxAmount));

        if ($digits === '') {
            return 0;
        }

        return min(self::MaxAmount, (int) $digits);
    }

    private function digitsBeforeDecimalSeparator(mixed $value, int $maxDigits = 5): string
    {
        $wholeNumber = preg_split('/[.,]/', (string) $value, 2)[0] ?? '';
        $digits = preg_replace('/\D/', '', $wholeNumber) ?? '';

        return mb_substr($digits, 0, $maxDigits);
    }

    public function addOneTimeSuggested(): void
    {
        if (count($this->suggestedOneTime) >= 6) {
            $this->dispatch('notify', message: 'Maximum 6 amounts allowed.', variant: 'danger');

            return;
        }

        $value = $this->amountToInteger($this->newOneTimeValue ?? 0);
        if ($value < 1 || $value > self::MaxAmount) {
            $this->dispatch('notify', message: 'Amount must be between 1 and 99,999.', variant: 'danger');
            $this->newOneTimeValue = null;

            return;
        }

        $this->suggestedOneTime[] = ['value' => $value, 'label' => ''];
        $this->sortSuggested($this->suggestedOneTime);
        $this->newOneTimeValue = null;
    }

    public function removeOneTimeSuggested(int $index): void
    {
        if (count($this->suggestedOneTime) <= 1) {
            $this->dispatch('notify', message: 'At least 1 amount is required.', variant: 'danger');

            return;
        }

        unset($this->suggestedOneTime[$index]);
        $this->suggestedOneTime = array_values($this->suggestedOneTime);
    }

    public function addMonthlySuggested(): void
    {
        if (count($this->suggestedMonthly) >= 6) {
            $this->dispatch('notify', message: 'Maximum 6 amounts allowed.', variant: 'danger');

            return;
        }

        $value = $this->amountToInteger($this->newMonthlyValue ?? 0);
        if ($value < 1 || $value > self::MaxAmount) {
            $this->dispatch('notify', message: 'Amount must be between 1 and 99,999.', variant: 'danger');
            $this->newMonthlyValue = null;

            return;
        }

        $this->suggestedMonthly[] = ['value' => $value, 'label' => ''];
        $this->sortSuggested($this->suggestedMonthly);
        $this->newMonthlyValue = null;
    }

    /**
     * Load the campaign's stored monthly upsell config into the editor state.
     */
    private function hydrateMonthlyUpsell(Campaign $campaign): void
    {
        $upsell = $campaign->config['monthly_upsell'] ?? [];

        if (! is_array($upsell)) {
            $upsell = [];
        }

        $this->upsell_enabled = (bool) ($upsell['enabled'] ?? false);
        $this->upsell_cooldown_days = (int) ($upsell['cooldown_days'] ?? 30);
        $this->upsell_heading = $upsell['heading'] ?? null;
        $this->upsell_body = $upsell['body'] ?? null;
        $this->upsell_decline_label = $upsell['decline_label'] ?? null;
        $storedTiers = is_array($upsell['tiers'] ?? null) ? $upsell['tiers'] : [];

        // A malformed entry must not take the page down: this editor is the
        // only place an admin could repair the config that broke it.
        $this->upsell_tiers = array_values(array_map(
            function (array $tier): array {
                $storedOffers = is_array($tier['offers'] ?? null) ? $tier['offers'] : [];

                return [
                    'min' => (float) ($tier['min'] ?? 0),
                    'max' => isset($tier['max']) && $tier['max'] !== '' ? (float) $tier['max'] : null,
                    'offers' => array_values(array_map(
                        fn (array $offer): array => [
                            'type' => $offer['type'] ?? 'percent',
                            'value' => (float) ($offer['value'] ?? 0),
                        ],
                        array_filter($storedOffers, is_array(...)),
                    )),
                ];
            },
            array_filter($storedTiers, is_array(...)),
        ));
    }

    /**
     * Worked examples of what a tier would offer donors, so an admin can see
     * the effect of their own percentages without guessing at the rounding.
     *
     * @return array<int, array{amount: float, offers: array<int, float>}>
     */
    public function upsellTierPreview(int $index): array
    {
        $tier = $this->upsell_tiers[$index] ?? null;

        if (! is_array($tier)) {
            return [];
        }

        $rules = new MonthlyUpsellRules;
        $minimum = (float) ($this->minimum_amount ?? 0);

        return array_map(
            fn (float $amount): array => [
                'amount' => $amount,
                'offers' => $rules->previewTier($tier, $amount, $minimum),
            ],
            $rules->previewAmountsFor($tier),
        );
    }

    /**
     * Offers in a tier that no donor will ever see, because a larger one in
     * the same tier always becomes the lighter button.
     *
     * @return array<int, string>
     */
    public function upsellUnusedOffers(int $index): array
    {
        $tier = $this->upsell_tiers[$index] ?? null;

        if (! is_array($tier)) {
            return [];
        }

        return (new MonthlyUpsellRules)->unusedOfferLabels(
            $tier,
            $this->default_currency,
            (float) ($this->minimum_amount ?? 0),
        );
    }

    /**
     * Switching the upsell on with no tiers configured would save a campaign
     * that is "enabled" but silent, so seed a starter tier the NGO can adjust.
     */
    public function updatedUpsellEnabled(bool $value): void
    {
        if ($value && $this->upsell_tiers === []) {
            $this->addUpsellTier();
        }
    }

    /**
     * Jump straight from the overview summary card to the upsell editor.
     */
    public function editMonthlyUpsell(): void
    {
        $this->activeTab = 'checkout';
        $this->checkoutPanel = 'upsell';
    }

    public function addUpsellTier(): void
    {
        if (count($this->upsell_tiers) >= self::MaxUpsellTiers) {
            $this->dispatch('notify', message: 'Maximum '.self::MaxUpsellTiers.' tiers allowed.', variant: 'danger');

            return;
        }

        $this->upsell_tiers[] = [
            'min' => 50.0,
            'max' => null,
            'offers' => [
                ['type' => 'percent', 'value' => 33.0],
                ['type' => 'percent', 'value' => 50.0],
            ],
        ];
    }

    /**
     * The monthly_upsell block to persist, or nothing at all for a campaign
     * that has never touched the feature - writing a disabled block into every
     * campaign on save would make "who configured this?" unanswerable.
     *
     * @return array<string, array<string, mixed>>
     */
    private function monthlyUpsellConfig(): array
    {
        $alreadyConfigured = array_key_exists('monthly_upsell', $this->campaign->config ?? []);

        if (! $this->upsell_enabled && $this->upsell_tiers === [] && ! $alreadyConfigured) {
            return [];
        }

        return [
            'monthly_upsell' => [
                'enabled' => $this->upsell_enabled,
                'cooldown_days' => $this->upsell_cooldown_days,
                'heading' => $this->upsell_heading ?: null,
                'body' => $this->upsell_body ?: null,
                'decline_label' => $this->upsell_decline_label ?: null,
                'tiers' => array_values($this->upsell_tiers),
            ],
        ];
    }

    public function removeUpsellTier(int $index): void
    {
        unset($this->upsell_tiers[$index]);
        $this->upsell_tiers = array_values($this->upsell_tiers);
    }

    public function removeMonthlySuggested(int $index): void
    {
        if (count($this->suggestedMonthly) <= 1) {
            $this->dispatch('notify', message: 'At least 1 amount is required.', variant: 'danger');

            return;
        }

        unset($this->suggestedMonthly[$index]);
        $this->suggestedMonthly = array_values($this->suggestedMonthly);
    }

    public function resetOneTimeDefaults(): void
    {
        $defaults = $this->defaultAmountsForCurrency($this->activeCurrency);
        $this->suggestedOneTime = $defaults['one_time'];
    }

    public function resetMonthlyDefaults(): void
    {
        $defaults = $this->defaultAmountsForCurrency($this->activeCurrency);
        $this->suggestedMonthly = $defaults['monthly'];
    }

    private function sortSuggested(array &$arr): void
    {
        usort($arr, fn (array $a, array $b) => $b['value'] <=> $a['value']);
    }

    /**
     * @param  array<int, array{value: int, label: string}>  $stored
     * @param  array<int, array{value: int, label: string}>  $defaults
     * @return array<int, array{value: int, label: string}>
     */
    private function backfillSuggestedDefaults(array $stored, array $defaults): array
    {
        $result = [];

        foreach ($defaults as $index => $default) {
            $value = (int) ($stored[$index]['value'] ?? 0);
            $result[$index] = $value > 0 ? ['value' => $value, 'label' => ''] : $default;
        }

        return $result;
    }

    public function getCurrencySymbolFor(string $currency): string
    {
        return match ($currency) {
            'USD' => '$',
            'SGD' => 'S$',
            'AUD' => 'A$',
            'GBP' => '£',
            'EUR' => '€',
            default => 'RM',
        };
    }

    public function getCurrencySymbol(): string
    {
        return $this->getCurrencySymbolFor($this->activeCurrency);
    }

    /**
     * Succeeded donation amounts for this campaign, bucketed by checkout channel.
     * Any source other than 'campaign_page' and 'virtual_terminal' (e.g. 'checkout_modal',
     * 'element', or null) is counted under 'checkout_modal'. A bucket is approximate
     * when it includes non-MYR donations converted at capture-time rates.
     *
     * @return array<string, array{amount: float, approximate: bool}>
     */
    /**
     * How the monthly upsell has performed on this campaign, over the window
     * the admin selected. Lifetime figures pool a tier or copy change with
     * everything that came before it, so the window is what makes a change
     * measurable.
     *
     * @return array{
     *     offers_shown: int,
     *     accepted: int,
     *     plans_started: int,
     *     added_monthly_value: float,
     *     is_approximate: bool,
     *     shows_rate: bool,
     *     took_own_amount: int,
     *     took_lighter: int,
     * }
     */
    #[Computed]
    public function upsellStats(): array
    {
        return app(MonthlyUpsellStats::class)->forCampaign($this->campaign, $this->upsellStatsDays());
    }

    /**
     * The selected window in days, or null for the whole history.
     */
    private function upsellStatsDays(): ?int
    {
        return match ($this->upsellStatsPeriod) {
            '30' => 30,
            '90' => 90,
            default => null,
        };
    }

    public function donationAmountsBySource(): array
    {
        $buckets = [
            'campaign_page' => ['amount' => 0.0, 'approximate' => false],
            'checkout_modal' => ['amount' => 0.0, 'approximate' => false],
            'virtual_terminal' => ['amount' => 0.0, 'approximate' => false],
        ];

        $this->campaign->donations()
            ->where('status', DonationStatus::Succeeded)
            ->get(['source', 'currency', 'base_amount', 'gross_amount'])
            ->each(function ($donation) use (&$buckets): void {
                $bucket = in_array($donation->source, ['campaign_page', 'virtual_terminal'], true)
                    ? $donation->source
                    : 'checkout_modal';

                $buckets[$bucket]['amount'] += (float) ($donation->base_amount ?? $donation->gross_amount);

                if (strtolower($donation->currency ?? '') !== 'myr') {
                    $buckets[$bucket]['approximate'] = true;
                }
            });

        return $buckets;
    }

    /**
     * Whether any succeeded donation was made in a non-MYR currency,
     * making MYR totals approximate (converted at capture-time rates).
     */
    public function hasApproximateRaisedTotals(): bool
    {
        return Donation::hasReportApproximations(
            $this->campaign->donations()->where('status', DonationStatus::Succeeded)->getQuery()
        );
    }

    /**
     * @return array<string, array<int, array{value: int|float, label: string}>>
     */
    public function getDefaultCurrencySuggestedAmounts(): array
    {
        $defaults = $this->defaultAmountsForCurrency($this->default_currency);
        $stored = $this->allSuggestedAmounts[$this->default_currency] ?? [];

        return [
            'one_time' => $this->backfillSuggestedDefaults($stored['one_time'] ?? [], $defaults['one_time']),
            'monthly' => $this->backfillSuggestedDefaults($stored['monthly'] ?? [], $defaults['monthly']),
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $rules = ['required', 'string', 'max:255'];

        // Titles that were already duplicated before this rule existed stay
        // saveable, so an unrelated edit is not held hostage by an old clash.
        if ($this->titleChanged()) {
            $rules[] = UniqueNameWithinOrganization::forCampaignTitle(
                $this->campaign->organization_id,
                $this->campaign->getKey(),
            );
        }

        return ['title' => $rules];
    }

    private function titleChanged(): bool
    {
        return Str::lower(trim($this->title)) !== Str::lower(trim((string) $this->campaign->title));
    }

    public function save(): void
    {
        if ($this->postDonationMode === 'redirect' && blank($this->redirect_url)) {
            $this->addError('redirect_url', 'Redirect URL is required when redirect mode is selected.');

            return;
        }

        if ($this->upsell_enabled) {
            $upsellErrors = (new MonthlyUpsellRules)->validateConfig($this->upsell_tiers);

            if ($upsellErrors !== []) {
                $this->addError('upsell_tiers', implode(' ', $upsellErrors));

                return;
            }
        }

        $validated = $this->validate();

        $org = Auth::user()?->organization;

        // Only block a move onto CHIP; a campaign already on it keeps working.
        $movingToChip = $this->payment_gateway === PaymentGateway::Chip->value
            && $this->campaign->payment_gateway !== PaymentGateway::Chip;

        if ($movingToChip && ! config('services.chip.donations_enabled')) {
            $this->addError('payment_gateway', 'CHIP is not available for campaigns yet.');

            return;
        }

        if ($this->payment_gateway === PaymentGateway::Chip->value && ! ($org?->chip_active)) {
            $this->addError('payment_gateway', 'CHIP is not configured for this organization.');

            return;
        }

        if (str_word_count(strip_tags((string) ($this->description ?? ''))) > 200) {
            $this->addError('description', 'Description cannot exceed 200 words.');

            return;
        }

        if (str_word_count($this->contentMessage ?? '') > 200) {
            $this->addError('contentMessage', 'Message must not exceed 200 words.');

            return;
        }

        // Save current active currency amounts back into nested array
        $defaults = $this->defaultAmountsForCurrency($this->activeCurrency);
        $this->allSuggestedAmounts[$this->activeCurrency] = [
            'one_time' => $this->backfillSuggestedDefaults($this->suggestedOneTime, $defaults['one_time']),
            'monthly' => $this->backfillSuggestedDefaults($this->suggestedMonthly, $defaults['monthly']),
        ];

        // Build flat legacy columns from backfilled active currency amounts
        $activeAmounts = $this->allSuggestedAmounts[$this->activeCurrency];

        $oneTime = collect($activeAmounts['one_time'] ?? [])
            ->filter(fn (array $item) => $item['value'] > 0)
            ->map(fn (array $item) => [
                'value' => (float) $item['value'],
                'label' => $item['label'] ?? null,
            ])
            ->values()
            ->toArray();

        $monthly = collect($activeAmounts['monthly'] ?? [])
            ->filter(fn (array $item) => $item['value'] > 0)
            ->map(fn (array $item) => [
                'value' => (float) $item['value'],
                'label' => $item['label'] ?? null,
            ])
            ->values()
            ->toArray();

        $config = array_merge($this->campaign->config ?? [], [
            'default_frequency' => $this->default_frequency,
            'default_amount' => (float) ($this->default_amount ?? 50),
            'default_currency' => $this->default_currency,
            'currency_autodetect' => $this->currency_autodetect,
            'suggested_amounts_by_currency' => $this->allSuggestedAmounts,
            'allow_cover_fee' => $this->allow_cover_fee,
            'show_comment' => $this->show_comment,
            'show_phone' => $this->show_phone,
            'post_donation_mode' => $this->postDonationMode,
            'share_channels' => $this->shareChannels,
            'share_message' => $this->shareMessage,
            'show_total_raised' => $this->show_total_raised,
            'content_title' => $this->contentTitle ?: null,
            'content_message' => $this->contentMessage ?: null,
            ...$this->monthlyUpsellConfig(),
        ]);

        $this->campaign->update([
            'title' => $validated['title'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
            'has_target' => $this->has_target,
            'target_amount' => $this->has_target ? ($validated['target_amount'] ?? null) : null,
            'has_end_date' => $this->has_end_date,
            'end_date' => $this->has_end_date ? ($validated['end_date'] ?? null) : null,
            'allow_recurring' => $this->allow_recurring,
            'allow_custom_amount' => $this->allow_custom_amount,
            'campaign_page_enabled' => $this->campaign_page_enabled,
            'payment_gateway' => $this->payment_gateway,
            'minimum_amount' => $validated['minimum_amount'] ?? null,
            'suggested_amounts' => null,
            'suggested_amounts_one_time' => $oneTime ?: null,
            'suggested_amounts_monthly' => $monthly ?: null,
            'thank_you_message' => $this->thank_you_message,
            'redirect_url' => $this->redirect_url,
            'config' => $config,
        ]);

        if ($this->image) {
            $path = $this->image->store('campaign-images', 'public');
            $this->campaign->update(['image_path' => $path]);
            $this->existing_image = $path;
            $this->image = null;
        }

        if ($this->contentLogo) {
            $path = $this->contentLogo->store("campaigns/{$this->campaign->id}/logos", 'public');
            $config = $this->campaign->config ?? [];
            $config['content_logo'] = $path;
            $this->campaign->update(['config' => $config]);
            $this->existingContentLogo = $path;
            $this->contentLogo = null;
        }

        if ($this->contentImage) {
            $path = $this->contentImage->store("campaigns/{$this->campaign->id}/images", 'public');
            $config = $this->campaign->config ?? [];
            $config['content_image'] = $path;
            $this->campaign->update(['config' => $config]);
            $this->existingContentImage = $path;
            $this->contentImage = null;
        }

        $this->dispatch('notify', message: 'Campaign saved.', variant: 'success');
    }

    public function confirmArchive(): void
    {
        $this->showArchiveModal = true;
    }

    public function archive(): void
    {
        $this->authorize('archive', $this->campaign);

        $this->campaign->update(['status' => CampaignStatus::Archived]);

        $this->redirectRoute('app.campaigns.index');
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->campaign);

        $this->campaign->delete();

        $this->redirectRoute('app.campaigns.index');
    }

    public function duplicate(): void
    {
        $this->authorize('create', Campaign::class);

        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $newCampaign = $this->campaign->replicate([
            'public_id',
            'slug',
            'form_parameter',
            'collected_amount',
            'image_path',
        ]);

        $newCampaign->title = $this->campaign->title.' (Copy)';
        $newCampaign->status = 'draft';
        $newCampaign->organization_id = $org->id;
        $newCampaign->save();

        $this->redirectRoute('app.campaigns.edit', $newCampaign);
    }

    public function removeImage(): void
    {
        $this->campaign->update(['image_path' => null]);
        $this->existing_image = null;
    }

    public function toggleHasTarget(): void
    {
        $this->has_target = ! $this->has_target;
    }

    public function toggleHasEndDate(): void
    {
        $this->has_end_date = ! $this->has_end_date;
    }

    public function toggleAllowCoverFee(): void
    {
        $this->allow_cover_fee = ! $this->allow_cover_fee;
    }

    public function render()
    {
        return view('livewire.app.campaigns.edit');
    }
}
