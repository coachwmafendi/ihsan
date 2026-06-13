<?php

declare(strict_types=1);

namespace App\Livewire\App\Campaigns;

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

    #[Locked]
    public Campaign $campaign;

    public string $activeTab = 'overview';

    public string $suggestedActiveFreq = 'one_time';

    /** @var string[] */
    public array $acceptedCurrencies = ['MYR'];

    public string $activeCurrency = 'MYR';

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

    #[Validate('nullable|string|max:5000')]
    public ?string $description = null;

    public ?string $existing_image = null;

    #[Validate('nullable|image|max:5120')]
    public $image = null;

    public bool $has_target = false;

    #[Validate('nullable|numeric|min:0|max:999999999.99')]
    public ?string $target_amount = null;

    public bool $has_end_date = false;

    #[Validate('nullable|date')]
    public ?string $end_date = null;

    public bool $allow_recurring = true;

    public bool $allow_custom_amount = true;

    #[Validate('nullable|numeric|min:0|max:999999999.99')]
    public ?string $minimum_amount = null;

    public string $default_frequency = 'one_time';

    #[Validate('nullable|numeric|min:0|max:999999999.99')]
    public ?string $default_amount = null;

    public ?string $newOneTimeValue = null;

    public ?string $newMonthlyValue = null;

    #[Validate('nullable|string|max:5000')]
    public ?string $thank_you_message = null;

    #[Validate('nullable|url|max:500')]
    public ?string $redirect_url = null;

    public function mount(Campaign $campaign): void
    {
        $this->authorize('update', $campaign);

        $this->campaign = $campaign;
        $this->title = $campaign->title;
        $this->status = $campaign->status->value;
        $this->description = $campaign->description;
        $this->existing_image = $campaign->image_path;
        $this->has_target = $campaign->has_target ?? false;
        $this->target_amount = $campaign->target_amount !== null ? (string) $campaign->target_amount : null;
        $this->has_end_date = $campaign->has_end_date ?? false;
        $this->end_date = $campaign->end_date?->format('Y-m-d');
        $this->allow_recurring = $campaign->allow_recurring ?? false;
        $this->allow_custom_amount = $campaign->allow_custom_amount ?? false;
        $this->minimum_amount = $campaign->minimum_amount !== null ? (string) $campaign->minimum_amount : null;
        $this->thank_you_message = $campaign->thank_you_message;
        $this->redirect_url = $campaign->redirect_url;

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
        $this->default_amount = isset($campaign->config['default_amount']) ? (string) $campaign->config['default_amount'] : null;
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
        $data = $this->allSuggestedAmounts[$this->activeCurrency] ?? $this->defaultAmountsForCurrency($this->activeCurrency);
        $this->suggestedOneTime = $data['one_time'] ?? [];
        $this->suggestedMonthly = $data['monthly'] ?? [];
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
                $val = (int) round((float) ($item['value'] ?? 0));

                return ['value' => max(1, min(99999, $val)), 'label' => $item['label'] ?? ''];
            }

            $val = (int) round((float) $item);

            return ['value' => max(1, min(99999, $val)), 'label' => ''];
        })->values()->toArray();
    }

    public function updated(string $property, mixed $value): void
    {
        // Clamp suggested amount values to 1–99999 when inputs blur
        if (str_starts_with($property, 'suggestedOneTime.') || str_starts_with($property, 'suggestedMonthly.')) {
            $val = (int) round((float) $value);
            if ($val < 1) {
                $val = 1;
            }
            if ($val > 99999) {
                $val = 99999;
            }
            data_set($this, $property, $val);
        }
    }

    public function addOneTimeSuggested(): void
    {
        if (count($this->suggestedOneTime) >= 6) {
            $this->dispatch('notify', message: 'Maximum 6 amounts allowed.', variant: 'danger');

            return;
        }

        $value = (int) round((float) ($this->newOneTimeValue ?? 0));
        if ($value < 1 || $value > 99999) {
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

        $value = (int) round((float) ($this->newMonthlyValue ?? 0));
        if ($value < 1 || $value > 99999) {
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

    public function getCurrencySymbol(): string
    {
        return match ($this->activeCurrency) {
            'USD' => '$',
            'SGD' => 'S$',
            'AUD' => 'A$',
            'GBP' => '£',
            'EUR' => '€',
            default => 'RM',
        };
    }

    public function save(): void
    {
        $validated = $this->validate();

        // Save current active currency amounts back into nested array
        $this->allSuggestedAmounts[$this->activeCurrency] = [
            'one_time' => $this->suggestedOneTime,
            'monthly' => $this->suggestedMonthly,
        ];

        // Build flat legacy columns from active currency only (for backward compat)
        $oneTime = collect($this->suggestedOneTime)
            ->filter(fn (array $item) => $item['value'] > 0)
            ->map(fn (array $item) => [
                'value' => (float) $item['value'],
                'label' => $item['label'] ?? null,
            ])
            ->values()
            ->toArray();

        $monthly = collect($this->suggestedMonthly)
            ->filter(fn (array $item) => $item['value'] > 0)
            ->map(fn (array $item) => [
                'value' => (float) $item['value'],
                'label' => $item['label'] ?? null,
            ])
            ->values()
            ->toArray();

        $config = array_merge($this->campaign->config ?? [], [
            'default_frequency' => $this->default_frequency,
            'default_amount' => $this->default_amount ? (float) $this->default_amount : null,
            'suggested_amounts_by_currency' => $this->allSuggestedAmounts,
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
            'minimum_amount' => $validated['minimum_amount'] ?? null,
            'suggested_amounts' => null,
            'suggested_amounts_one_time' => $oneTime ?: null,
            'suggested_amounts_monthly' => $monthly ?: null,
            'thank_you_message' => $validated['thank_you_message'] ?? null,
            'redirect_url' => $validated['redirect_url'] ?? null,
            'config' => $config,
        ]);

        if ($this->image) {
            $path = $this->image->store('campaign-images', 'public');
            $this->campaign->update(['image_path' => $path]);
            $this->existing_image = $path;
            $this->image = null;
        }

        $this->dispatch('notify', message: 'Campaign saved.', variant: 'success');
    }

    public function archive(): void
    {
        $this->authorize('update', $this->campaign);

        $this->campaign->update(['status' => 'archived']);
        $this->status = 'archived';

        $this->dispatch('notify', message: 'Campaign archived.', variant: 'success');
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
            'form_parameter',
            'collected_amount',
            'image_path',
        ]);

        $newCampaign->title = $this->campaign->title . ' (Copy)';
        $newCampaign->status = 'draft';
        $newCampaign->organization_id = $org->id;
        $newCampaign->save();

        $this->redirectRoute('app.campaigns.show', $newCampaign);
    }

    public function removeImage(): void
    {
        $this->campaign->update(['image_path' => null]);
        $this->existing_image = null;
    }

    public function render()
    {
        return view('livewire.app.campaigns.edit', [
            'title' => 'Edit Campaign',
        ]);
    }
}
