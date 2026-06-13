<?php

declare(strict_types=1);

namespace App\Livewire\App\Campaigns;

use App\Models\Campaign;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\WithFileUploads;

#[Layout('layouts.app')]
class CampaignCreate extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('required|string|in:active,draft')]
    public string $status = 'draft';

    #[Validate('nullable|string|max:5000')]
    public ?string $description = null;

    #[Validate('nullable|image|max:5120')]
    public $image = null;

    public bool $has_target = false;

    #[Validate('nullable|numeric|min:0|max:999999999.99')]
    public ?string $target_amount = null;

    public bool $has_end_date = false;

    #[Validate('nullable|date|after:today')]
    public ?string $end_date = null;

    public bool $allow_recurring = true;

    public bool $allow_custom_amount = true;

    #[Validate('nullable|numeric|min:0|max:999999999.99')]
    public ?string $minimum_amount = null;

    public array $suggested_amounts = [];

    public string $default_frequency = 'one_time';

    #[Validate('nullable|numeric|min:0|max:999999999.99')]
    public ?string $default_amount = null;

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

        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $suggested = collect($this->suggested_amounts)
            ->filter(fn (array $item) => filled($item['value'] ?? null))
            ->map(fn (array $item) => [
                'value' => (float) $item['value'],
                'label' => $item['label'] ?? null,
            ])
            ->values()
            ->toArray();

        $config = [
            'default_frequency' => $this->default_frequency,
            'default_amount' => $this->default_amount ? (float) $this->default_amount : null,
            'allow_cover_fee' => true,
            'show_comment' => true,
        ];

        $campaign = new Campaign([
            'organization_id' => $org->id,
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
            'config' => $config,
        ]);

        if ($this->image) {
            $campaign->image_path = $this->image->store('campaign-images', 'public');
        }

        $campaign->save();

        $this->redirectRoute('app.campaigns.show', $campaign);
    }

    public function render()
    {
        return view('livewire.app.campaigns.create', [
            'title' => 'Create Campaign',
        ]);
    }
}
