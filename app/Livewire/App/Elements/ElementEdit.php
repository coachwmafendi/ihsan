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

    public bool $showArchiveModal = false;

    public string $config_title = 'Support our cause';

    #[Validate('nullable|string|max:100')]
    public string $config_message = 'Give waqf today. May Allah accept our waqf and bless our families with endless rewards.';

    public ?string $config_button_text = null;

    #[Validate('nullable|string|in:small,medium,large,extra large')]
    public string $config_size = 'medium';

    public string $config_button_color = 'bg-blue-600 hover:bg-blue-700';

    public string $config_button_size = 'text-base px-6 py-3';

    public int $config_corner_radius = 8;

    public string $config_button_icon = 'heart';

    public string $config_button_effect = 'none';

    #[Validate('nullable|string|in:left,center,right')]
    public string $config_alignment = 'center';

    public string $config_position = 'right-center';

    #[Validate('nullable|string|in:checkout_modal,open_campaign_page')]
    public string $config_action = 'checkout_modal';

    #[Validate('nullable|string|in:after_delay,immediately,on_scroll,on_exit')]
    public string $config_trigger = 'after_delay';

    #[Validate('integer|min:0|max:3600')]
    public int $config_delay = 8;

    #[Validate('nullable|string|in:once,once_per_day,once_per_session,once_per_week,once_per_month')]
    public string $config_frequency = 'once_per_day';

    #[Validate('nullable|string|in:desktop_mobile,desktop_only,mobile_only')]
    public string $config_visibility = 'desktop_mobile';

    #[Validate('nullable|string|in:simple,full')]
    public string $config_layout = 'simple';

    #[Validate('nullable|string|max:2048')]
    public string $config_image_url = 'https://images.unsplash.com/photo-1629273229664-11fabc0becc0?q=80&w=2062';

    #[Validate('nullable|string|in:campaign,blue,teal,green,orange,red,purple,dark')]
    public string $config_color = 'campaign';

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
        $this->config_title = $config['title'] ?? 'Support our cause';
        $this->config_message = $config['message'] ?? 'Give waqf today. May Allah accept our waqf and bless our families with endless rewards.';
        $this->config_button_text = $this->element->type === ElementType::QrCode
            ? ($config['label'] ?? 'Scan to donate')
            : ($config['button_text'] ?? $config['text'] ?? $config['submit_text'] ?? 'Donate');
        $this->config_size = $config['size'] ?? 'medium';
        $this->config_alignment = $config['alignment'] ?? 'center';
        $this->config_button_color = $config['button_color'] ?? $config['color'] ?? 'bg-blue-600 hover:bg-blue-700';
        $this->config_button_size = $config['button_size'] ?? 'text-base px-6 py-3';
        $this->config_corner_radius = (int) ($config['corner_radius'] ?? 8);
        $this->config_button_icon = $config['button_icon'] ?? $config['icon'] ?? 'heart';
        $this->config_button_effect = $config['button_effect'] ?? 'none';
        $this->config_alignment = $config['alignment'] ?? 'center';
        $this->config_position = $config['position'] ?? 'right-center';

        $this->config_action = $config['action'] ?? 'checkout_modal';
        $this->config_trigger = $config['trigger'] ?? 'after_delay';
        $this->config_delay = (int) ($config['delay'] ?? $config['delay_seconds'] ?? 8);
        $this->config_frequency = $config['frequency'] ?? 'once_per_day';
        $this->config_visibility = $config['visibility'] ?? 'desktop_mobile';
        $this->config_layout = $config['layout'] ?? 'simple';
        $this->config_image_url = $config['image_url'] ?? 'https://images.unsplash.com/photo-1629273229664-11fabc0becc0?q=80&w=2062';
        $this->config_color = $config['color'] ?? 'campaign';
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

        $defaultTitle = 'Support our cause';
        $defaultMessage = 'Give waqf today. May Allah accept our waqf and bless our families with endless rewards.';
        $usesContent = in_array($this->element->type, [ElementType::Form, ElementType::Popup, ElementType::QrCode], true);

        $config = array_filter([
            'title' => $usesContent ? (filled($this->config_title) ? $this->config_title : $defaultTitle) : $this->config_title,
            'message' => $usesContent ? (filled($this->config_message) ? $this->config_message : $defaultMessage) : $this->config_message,
            'button_text' => $this->element->type === ElementType::QrCode ? null : $this->config_button_text,
            'submit_text' => $this->element->type === ElementType::Form ? $this->config_button_text : null,
            'label' => $this->element->type === ElementType::QrCode ? $this->config_button_text : null,
            'size' => $this->element->type === ElementType::QrCode ? $this->config_size : null,
            'alignment' => $this->element->type === ElementType::QrCode ? $this->config_alignment : null,
        ], fn ($value) => $value !== null && $value !== '');

        if ($this->usesButtonStyleConfig()) {
            $config['button_color'] = $this->config_button_color;
            $config['button_size'] = $this->config_button_size;
            $config['corner_radius'] = $this->config_corner_radius;
            $config['button_icon'] = $this->config_button_icon;
            $config['icon'] = $this->config_button_icon;
            $config['button_effect'] = $this->config_button_effect;
            $config['alignment'] = $this->config_alignment;
        }

        if ($this->element->type === ElementType::StickyButton) {
            $config['position'] = $this->config_position;
        }

        if ($this->element->type === ElementType::Popup) {
            $config['action'] = $this->config_action;
            $config['trigger'] = $this->config_trigger;
            $config['delay'] = $this->config_delay;
            $config['frequency'] = $this->config_frequency;
            $config['visibility'] = $this->config_visibility;
            $config['layout'] = $this->config_layout;
            $config['image_url'] = $this->config_image_url;
            $config['color'] = $this->config_color;
            $config['button_effect'] = $this->config_button_effect;
        }

        // Preserve existing config keys that aren't managed by this form
        $existingConfig = $this->element->config ?? [];
        $mergedConfig = array_merge($existingConfig, $config);

        if ($this->usesButtonStyleConfig()) {
            unset($mergedConfig['title'], $mergedConfig['message']);
        }

        if ($this->element->type === ElementType::Popup) {
            foreach (['button_color', 'button_size', 'corner_radius', 'button_icon', 'icon', 'alignment', 'position', 'submit_text'] as $key) {
                unset($mergedConfig[$key]);
            }
        }

        if ($this->element->type === ElementType::QrCode) {
            foreach (['button_text', 'text', 'submit_text'] as $key) {
                unset($mergedConfig[$key]);
            }
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

    public function confirmArchive(): void
    {
        $this->showArchiveModal = true;
    }

    public function archive(): void
    {
        $this->authorize('archive', $this->element);

        $this->element->archive();

        $this->redirectRoute('app.elements.index');
    }

    public function render()
    {
        return view('livewire.app.elements.edit', [
            'title' => 'Edit Element',
        ]);
    }
}
