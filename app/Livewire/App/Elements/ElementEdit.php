<?php

declare(strict_types=1);

namespace App\Livewire\App\Elements;

use App\Models\Campaign;
use App\Models\Element;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class ElementEdit extends Component
{
    #[Locked]
    public Element $element;

    #[Validate('required|exists:campaigns,id')]
    public ?int $campaign_id = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    public bool $is_active = true;

    public ?string $config_title = null;

    public ?string $config_message = null;

    public ?string $config_button_text = null;

    #[Computed]
    public function organization()
    {
        return Auth::user()?->organization;
    }

    #[Computed]
    public function campaigns()
    {
        $org = $this->organization;

        if (! $org) {
            return collect();
        }

        return Campaign::query()
            ->where('organization_id', $org->id)
            ->orderBy('title')
            ->get();
    }

    public function mount(Element $element): void
    {
        $this->authorize('update', $element);

        $this->element = $element;
        $this->campaign_id = $element->campaign_id;
        $this->name = $element->name;
        $this->is_active = $element->is_active;

        $config = $element->config ?? [];
        $this->config_title = $config['title'] ?? null;
        $this->config_message = $config['message'] ?? null;
        $this->config_button_text = $config['button_text'] ?? null;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $org = $this->organization;

        if (! $org) {
            return;
        }

        $campaign = Campaign::find($validated['campaign_id']);

        if (! $campaign || $campaign->organization_id !== $org->id) {
            abort(403);
        }

        $config = array_filter([
            'title' => $this->config_title,
            'message' => $this->config_message,
            'button_text' => $this->config_button_text,
        ]);

        // Preserve existing config keys that aren't managed by this form
        $existingConfig = $this->element->config ?? [];
        $mergedConfig = array_merge($existingConfig, $config);

        $this->element->update([
            'campaign_id' => $validated['campaign_id'],
            'name' => $validated['name'],
            'is_active' => $this->is_active,
            'config' => $mergedConfig ?: null,
        ]);

        $this->dispatch('notify', message: 'Element saved.', variant: 'success');
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->element);

        $this->element->delete();

        $this->redirectRoute('app.elements.index');
    }

    public function render()
    {
        return view('livewire.app.elements.edit', [
            'title' => 'Edit Element',
        ]);
    }
}
