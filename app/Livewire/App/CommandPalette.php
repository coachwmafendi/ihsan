<?php

declare(strict_types=1);

namespace App\Livewire\App;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CommandPalette extends Component
{
    public bool $open = false;

    #[On('open-command-palette')]
    public function openPalette(): void
    {
        $this->open = true;
    }

    public function closePalette(): void
    {
        $this->open = false;
    }

    /**
     * @return array{pages: array<int, array{label: string, route: string}>, actions: array<int, array{label: string, hotkey: string, url: string}>}
     */
    #[Computed]
    public function items(): array
    {
        return [
            'pages' => [
                ['label' => 'Dashboard', 'route' => 'app.dashboard'],
                ['label' => 'UI Registry', 'route' => 'app.elements.index'],
                ['label' => 'Donations', 'route' => 'app.donations.index'],
                ['label' => 'Campaigns', 'route' => 'app.campaigns.index'],
            ],
            'actions' => [
                ['label' => 'New donation record', 'hotkey' => 'D', 'url' => route('app.donations.index')],
                ['label' => 'Create campaign', 'hotkey' => 'K', 'url' => route('app.campaigns.index', ['create' => 1])],
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.app.command-palette', [
            'pages' => collect($this->items()['pages'])->map(fn (array $p) => [
                'label' => $p['label'],
                'url' => route($p['route']),
            ])->values()->all(),
            'actions' => $this->items()['actions'],
        ]);
    }
}
