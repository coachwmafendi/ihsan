<?php

declare(strict_types=1);

namespace App\Livewire\App;

use App\Models\Donor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CommandPalette extends Component
{
    public bool $open = false;

    public string $query = '';

    #[On('open-command-palette')]
    public function openPalette(): void
    {
        $this->open = true;
    }

    public function closePalette(): void
    {
        $this->open = false;
        $this->reset('query');
    }

    public function updatedOpen(): void
    {
        if (! $this->open) {
            $this->reset('query');
        }
    }

    #[Computed]
    public function organization()
    {
        return Auth::user()?->organization;
    }

    /**
     * @return array<int, array{label: string, url: string}>
     */
    #[Computed]
    public function pages(): array
    {
        $pages = [
            ['label' => 'Dashboard', 'route' => 'app.dashboard'],
            ['label' => 'Supporters', 'route' => 'app.supporters.index'],
            ['label' => 'Donations', 'route' => 'app.donations.index'],
            ['label' => 'Campaigns', 'route' => 'app.campaigns.index'],
        ];

        return collect($pages)
            ->filter(fn (array $p) => str_contains(strtolower($p['label']), strtolower($this->query)))
            ->map(fn (array $p) => ['label' => $p['label'], 'url' => route($p['route'])])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, hotkey: string, url: string}>
     */
    #[Computed]
    public function actions(): array
    {
        $actions = [
            ['label' => 'New element', 'hotkey' => 'E', 'url' => route('app.elements.create')],
            ['label' => 'Create campaign', 'hotkey' => 'K', 'url' => route('app.campaigns.index', ['create' => 1])],
        ];

        return collect($actions)
            ->filter(fn (array $a) => str_contains(strtolower($a['label']), strtolower($this->query)))
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{label: string, sublabel: string, url: string}>
     */
    #[Computed]
    public function supporters(): array
    {
        if (strlen($this->query) < 2) {
            return [];
        }

        $org = $this->organization;
        $search = '%'.$this->query.'%';

        return Donor::query()
            ->select('donors.public_id', 'donors.name', 'donors.email')
            ->when(
                $org,
                fn (Builder $q) => $q->whereHas('donations.campaign', fn (Builder $cq) => $cq->where('organization_id', $org->id)),
                fn (Builder $q) => $q->whereRaw('1 = 0')
            )
            ->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search);
            })
            ->limit(5)
            ->get()
            ->map(fn (Donor $donor) => [
                'label' => $donor->name,
                'sublabel' => $donor->email,
                'url' => route('app.supporters.show', $donor),
            ])
            ->all();
    }

    /**
     * @return array{pages: array<int, array{label: string, url: string}>, actions: array<int, array{label: string, hotkey: string, url: string}>}
     */
    #[Computed]
    public function items(): array
    {
        return [
            'pages' => $this->pages(),
            'actions' => $this->actions(),
        ];
    }

    public function render(): View
    {
        return view('livewire.app.command-palette', [
            'pages' => $this->pages(),
            'actions' => $this->actions(),
            'supporters' => $this->supporters(),
            'hasResults' => count($this->pages()) > 0 || count($this->actions()) > 0 || count($this->supporters()) > 0,
        ]);
    }
}
