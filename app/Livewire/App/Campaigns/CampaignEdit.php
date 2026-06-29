<?php

declare(strict_types=1);

namespace App\Livewire\App\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Layout('layouts.app')]
class CampaignEdit extends Component
{
    use WithFileUploads;

    private const MaxAmount = 99999;

    private const MaxTargetAmount = 9999999;

    #[Locked]
    public Campaign $campaign;

    public string $activeTab = 'overview';

    public bool $showArchiveModal = false;

    public string $suggestedActiveFreq = 'one_time';

    public string $checkoutPanel = 'currency';

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

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|in:active,draft,paused,ended,archived')]
    public string $status = 'draft';

    #[Validate('required|string|in:stripe,chip')]
    public string $paymentGateway = 'stripe';

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
        $this->paymentGateway = $campaign->payment_gateway?->value ?? 'stripe';
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

    public function save(): void
    {
        if ($this->postDonationMode === 'redirect' && blank($this->redirect_url)) {
            $this->addError('redirect_url', 'Redirect URL is required when redirect mode is selected.');

            return;
        }

        $validated = $this->validate();

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
            'minimum_amount' => $validated['minimum_amount'] ?? null,
            'payment_gateway' => $validated['paymentGateway'],
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
        return view('livewire.app.campaigns.edit', [
            'title' => 'Edit Campaign',
        ]);
    }
}
