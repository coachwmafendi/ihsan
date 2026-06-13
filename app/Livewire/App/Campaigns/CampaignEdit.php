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

    public string $activeTab = 'settings';

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

    public array $suggested_amounts = [];

    public string $default_frequency = 'one_time';

    #[Validate('nullable|numeric|min:0|max:999999999.99')]
    public ?string $default_amount = null;

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

        $rawSuggested = $campaign->suggested_amounts ?? [];
        $this->suggested_amounts = collect($rawSuggested)->map(function ($item) {
            if (is_array($item)) {
                return ['value' => $item['value'] ?? '', 'label' => $item['label'] ?? ''];
            }

            return ['value' => (string) $item, 'label' => ''];
        })->values()->toArray();
        $this->default_frequency = $campaign->config['default_frequency'] ?? 'one_time';
        $this->default_amount = isset($campaign->config['default_amount']) ? (string) $campaign->config['default_amount'] : null;
    }

    public function addSuggestedAmount(): void
    {
        $this->suggested_amounts[] = ['value' => '', 'label' => ''];
    }

    public function removeSuggestedAmount(int $index): void
    {
        unset($this->suggested_amounts[$index]);
        $this->suggested_amounts = array_values($this->suggested_amounts);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $suggested = collect($this->suggested_amounts)
            ->filter(fn (array $item) => filled($item['value'] ?? null))
            ->map(fn (array $item) => [
                'value' => (float) $item['value'],
                'label' => $item['label'] ?? null,
            ])
            ->values()
            ->toArray();

        $config = array_merge($this->campaign->config ?? [], [
            'default_frequency' => $this->default_frequency,
            'default_amount' => $this->default_amount ? (float) $this->default_amount : null,
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
            'suggested_amounts' => $suggested ?: null,
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

        $this->dispatch('toast', type: 'success', message: 'Campaign saved.');
    }

    public function archive(): void
    {
        $this->authorize('update', $this->campaign);

        $this->campaign->update(['status' => 'archived']);
        $this->status = 'archived';

        $this->dispatch('toast', type: 'success', message: 'Campaign archived.');
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

        $newCampaign->title = $this->campaign->title.' (Copy)';
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
