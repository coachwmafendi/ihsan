<?php

declare(strict_types=1);

namespace App\Livewire\App;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Topbar extends Component
{
    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->refreshUnreadCount();
    }

    #[On('notification-read')]
    public function refreshUnreadCount(): void
    {
        $this->unreadCount = Auth::user()?->getUnreadNotificationsCountAttribute() ?? 0;
    }

    #[On('account-updated')]
    public function refreshProfile(): void
    {
        // Re-renders the topbar so the new avatar/name are reflected.
    }

    public function render()
    {
        return view('livewire.app.topbar', [
            'organization' => Auth::user()?->organization,
        ]);
    }
}
