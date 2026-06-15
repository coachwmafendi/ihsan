<?php

declare(strict_types=1);

namespace App\Livewire\App\Elements;

use App\Models\Campaign;
use App\Models\Element;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class ElementCreate extends Component
{
    #[Validate('required|exists:campaigns,id')]
    public ?int $campaign_id = null;

    #[Validate('required|string|in:button,floating_button,form,popup,link,sticky_button,qr_code')]
    public string $type = 'button';

    #[Validate('required|string|max:255')]
    public string $name = '';

    public bool $is_active = true;

    public string $config_title = 'Support our cause';

    #[Validate('nullable|string|max:100')]
    public string $config_message = 'Give waqf today. May Allah accept our waqf and bless our families with endless rewards.';

    public ?string $config_button_text = null;

    public string $config_size = 'medium';

    public string $config_alignment = 'center';

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

    #[Validate('nullable|string|in:none,gradient_teal_green,gradient_blue_purple,gradient_orange_red,gradient_rose_pink,gradient_amber_orange,gradient_cyan_blue,gradient_emerald_teal,gradient_indigo_purple,gradient_gold_amber,gradient_pink_purple')]
    public string $config_button_effect = 'none';

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

        $config = array_filter([
            'button_text' => $this->config_button_text,
        ]);

        if (in_array($validated['type'], ['form', 'popup', 'qr_code'], true)) {
            $config['title'] = filled($this->config_title) ? $this->config_title : $defaultTitle;
            $config['message'] = filled($this->config_message) ? $this->config_message : $defaultMessage;

            if ($validated['type'] === 'form') {
                $config['submit_text'] = $this->config_button_text;
            }

            if ($validated['type'] === 'qr_code') {
                $config['label'] = filled($this->config_button_text) ? $this->config_button_text : 'Scan to donate';
                $config['size'] = $this->config_size;
                $config['alignment'] = $this->config_alignment;
            }
        }

        if ($validated['type'] === 'popup') {
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

        $element = new Element([
            'organization_id' => $org->id,
            'campaign_id' => $validated['campaign_id'],
            'type' => $validated['type'],
            'name' => $validated['name'],
            'token' => $this->generateUniqueToken(),
            'is_active' => $this->is_active,
            'config' => $config ?: null,
        ]);

        $element->save();

        $this->dispatch('notify', message: 'Element created successfully.', variant: 'success');

        $this->redirectRoute('app.elements.index');
    }

    private function generateUniqueToken(): string
    {
        $attempts = 0;

        do {
            $token = Str::random(6);
            $exists = Element::where('token', $token)->exists();
            $attempts++;
        } while ($exists && $attempts < 10);

        return $token;
    }

    public function render()
    {
        return view('livewire.app.elements.create', [
            'title' => 'Create Element',
        ]);
    }
}
