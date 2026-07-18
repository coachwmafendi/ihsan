<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Models\Element;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

/**
 * Shared source-channel and element filter state for index pages.
 * The host component must expose an `organization` computed property
 * and apply the selections in its own base query.
 */
trait HasSourceAndElementFilters
{
    /** @var array<int, string> */
    #[Url(except: [])]
    public array $sourceFilter = [];

    /** @var array<int, string> */
    #[Url(except: [])]
    public array $elementFilter = [];

    public function updatedSourceFilter(): void
    {
        $this->resetPage();
    }

    public function clearSourceFilter(): void
    {
        $this->sourceFilter = [];
        $this->resetPage();
    }

    public function updatedElementFilter(): void
    {
        $this->resetPage();
    }

    public function clearElementFilter(): void
    {
        $this->elementFilter = [];
        $this->resetPage();
    }

    /**
     * @return array<string, string>
     */
    public function sourceOptions(): array
    {
        return [
            'checkout_modal' => 'Checkout Modal',
            'campaign_page' => 'Campaign Page',
            'virtual_terminal' => 'Virtual Terminal',
        ];
    }

    /**
     * Source values behind each option; element and legacy null count as Checkout Modal.
     *
     * @return array<int, string>
     */
    public function selectedSourceValues(): array
    {
        $sources = array_intersect($this->sourceFilter, array_keys($this->sourceOptions()));

        if (in_array('checkout_modal', $sources, true)) {
            $sources[] = 'element';
        }

        return array_values($sources);
    }

    public function sourceChipLabel(): string
    {
        return $this->summariseSelection('Source', array_values(array_intersect_key($this->sourceOptions(), array_flip($this->sourceFilter))));
    }

    #[Computed]
    public function elementOptions()
    {
        $org = $this->organization;

        if (! $org) {
            return collect();
        }

        return Element::where('organization_id', $org->id)
            ->orderBy('name')
            ->get(['id', 'name', 'token']);
    }

    public function elementChipLabel(): string
    {
        $selected = $this->elementOptions
            ->whereIn('token', $this->elementFilter)
            ->pluck('name')
            ->values()
            ->all();

        return $this->summariseSelection('Element', $selected);
    }

    /**
     * @param  array<int, string>  $selected
     */
    private function summariseSelection(string $fallback, array $selected): string
    {
        return match (count($selected)) {
            0 => $fallback,
            1 => $selected[0],
            default => $selected[0].' and '.(count($selected) - 1).' more',
        };
    }
}
