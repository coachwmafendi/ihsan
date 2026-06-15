<?php

declare(strict_types=1);

namespace App\Livewire\App\Notifications;

use App\Notifications\AdminToOrgAdminNotification;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public function markAsRead(string $id): void
    {
        $notification = Auth::user()?->notifications()
            ->where('id', $id)
            ->where('type', AdminToOrgAdminNotification::class)
            ->first();

        if ($notification === null) {
            return;
        }

        $notification->markAsRead();

        $this->dispatch('notification-read');
    }

    public function delete(string $id): void
    {
        $notification = Auth::user()?->notifications()
            ->where('id', $id)
            ->where('type', AdminToOrgAdminNotification::class)
            ->first();

        if ($notification === null) {
            return;
        }

        $notification->delete();

        $this->dispatch('notification-read');
    }

    #[Computed]
    public function notifications()
    {
        return Auth::user()?->notifications()
            ->where('type', AdminToOrgAdminNotification::class)
            ->orderByDesc('created_at')
            ->paginate(15);
    }

    public function render()
    {
        return view('livewire.app.notifications.index', [
            'title' => 'Notifications',
        ]);
    }
}
