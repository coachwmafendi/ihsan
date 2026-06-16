<?php

declare(strict_types=1);

namespace App\Livewire\App\Settings;

use App\Models\Element;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Installation extends Component
{
    public string $activeTab = 'html';

    /** @return array<int, Element> */
    #[Computed]
    public function elements(): array
    {
        return Element::where('organization_id', Auth::user()?->organization?->id)
            ->whereNull('archived_at')
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function widgetUrl(): string
    {
        return url('/e/widget.js');
    }

    public function render(): View
    {
        return view('livewire.app.settings.installation');
    }
}
