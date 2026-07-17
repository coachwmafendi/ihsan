<?php

declare(strict_types=1);

namespace App\Livewire\App;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Coming Soon')]
class PlaceholderPage extends Component
{
    public string $title = 'Coming Soon';

    public string $description = 'This feature is coming soon.';

    public function mount(string $page = ''): void
    {
        $titles = [
            'payouts' => 'Payouts',
            'members' => 'Members',
            'teams' => 'Teams',
            'api-keys' => 'API Keys',
            'webhooks' => 'Webhooks',
            'embed-forms' => 'Embed Forms',
        ];
        $this->title = $titles[$page] ?? 'Coming Soon';
    }

    public function render()
    {
        return view('livewire.app.placeholder');
    }
}
