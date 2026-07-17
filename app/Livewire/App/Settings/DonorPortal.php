<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use App\Models\Organization;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Settings — Donor Portal')]
class DonorPortal extends Component
{
    public ?string $portal_reply_to_email = null;

    public ?string $portal_tagline = null;

    public ?string $portal_receipt_footer = null;

    public function mount(): void
    {
        /** @var Organization|null $org */
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        /** @var array<string, mixed> $settings */
        $settings = $org->settings ?? [];
        $this->portal_reply_to_email = $settings['portal_reply_to_email'] ?? $org->contact_email;
        $this->portal_tagline = $settings['portal_tagline'] ?? null;
        $this->portal_receipt_footer = $settings['portal_receipt_footer'] ?? null;
    }

    public function save(): void
    {
        $this->validate([
            'portal_reply_to_email' => ['nullable', 'email', 'max:255'],
            'portal_tagline' => ['nullable', 'string', 'max:255'],
            'portal_receipt_footer' => ['nullable', 'string', 'max:2000'],
        ]);

        /** @var Organization|null $org */
        $org = Auth::user()?->organization;

        if (! $org) {
            return;
        }

        $settings = array_merge($org->settings ?? [], [
            'portal_reply_to_email' => $this->portal_reply_to_email,
            'portal_tagline' => $this->portal_tagline,
            'portal_receipt_footer' => $this->portal_receipt_footer,
        ]);

        $org->update(['settings' => $settings]);

        $this->dispatch('notify', message: 'Donor portal settings saved.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.app.settings.donor-portal');
    }
}
