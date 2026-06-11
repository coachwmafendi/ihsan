<?php

namespace App\Filament\App\Pages;

use Filament\Notifications\Notification;
use Filament\Pages\Page;

class Notifications extends Page
{
    protected static ?string $title = 'Notifications';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected static string|\UnitEnum|null $navigationGroup = 'Account';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationBadgeColor = 'danger';

    protected string $view = 'filament.app.pages.notifications';

    public static function getNavigationBadge(): ?string
    {
        $count = auth()->user()?->unread_notifications_count ?? 0;

        return $count > 0 ? (string) $count : null;
    }

    public int $perPage = 15;

    public function markAsRead(string $notificationId): void
    {
        $notification = auth()->user()->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();

            Notification::make()
                ->success()
                ->title('Notification marked as read')
                ->send();
        }

        $this->redirect(route('filament.app.pages.notifications'));
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications->markAsRead();

        Notification::make()
            ->success()
            ->title('All notifications marked as read')
            ->send();

        $this->redirect(route('filament.app.pages.notifications'));
    }
}
