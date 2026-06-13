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

    #[Validate('required|string|in:button,floating_button,form,popup')]
    public string $type = 'button';

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
