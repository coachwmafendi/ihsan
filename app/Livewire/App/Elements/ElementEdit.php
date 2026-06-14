<?php

declare(strict_types=1);

namespace App\Livewire\App\Elements;

use App\Enums\ElementType;
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

    public string $config_button_color = 'bg-blue-600 hover:bg-blue-700';

    public string $config_button_size = 'text-base px-6 py-3';

    public int $config_corner_radius = 8;

    public string $config_button_icon = 'heart';

    public string $config_button_effect = 'none';

    public string $config_alignment = 'center';

    public string $config_position = 'right-center';

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

    private function usesButtonStyleConfig(): bool
    {
        return in_array($this->element->type, [
            ElementType::Button,
            ElementType::FloatingButton,
            ElementType::StickyButton,
            ElementType::Link,
        ], true);
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
        $this->config_button_text = $config['button_text'] ?? $config['text'] ?? 'Donate';
        $this->config_button_color = $config['button_color'] ?? $config['color'] ?? 'bg-blue-600 hover:bg-blue-700';
        $this->config_button_size = $config['button_size'] ?? 'text-base px-6 py-3';
        $this->config_corner_radius = (int) ($config['corner_radius'] ?? 8);
        $this->config_button_icon = $config['button_icon'] ?? $config['icon'] ?? 'heart';
        $this->config_button_effect = $config['button_effect'] ?? 'none';
        $this->config_alignment = $config['alignment'] ?? 'center';
        $this->config_position = $config['position'] ?? 'right-center';
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
        ], fn ($value) => $value !== null && $value !== '');

        if ($this->usesButtonStyleConfig()) {
            $config['button_color'] = $this->config_button_color;
            $config['button_size'] = $this->config_button_size;
            $config['corner_radius'] = $this->config_corner_radius;
            $config['button_icon'] = $this->config_button_icon;
            $config['button_effect'] = $this->config_button_effect;
            $config['alignment'] = $this->config_alignment;
        }

        if ($this->element->type === ElementType::StickyButton) {
            $config['position'] = $this->config_position;
        }

        // Preserve existing config keys that aren't managed by this form
        $existingConfig = $this->element->config ?? [];
        $mergedConfig = array_merge($existingConfig, $config);

        if ($this->usesButtonStyleConfig()) {
            unset($mergedConfig['title'], $mergedConfig['message']);
        }

        if ($this->element->type === ElementType::Link) {
            $mergedConfig['text'] = $this->config_button_text;
            $mergedConfig['style'] = 'button';
            $mergedConfig['action'] = 'checkout_modal';
        }

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
