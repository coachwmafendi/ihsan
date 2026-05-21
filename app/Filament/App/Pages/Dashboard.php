<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Facades\FilamentView;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = null;

    protected string $view = 'filament.app.pages.dashboard';

    public static function getNavigationLabel(): string
    {
        return '';
    }

    public function getHeading(): string
    {
        return '';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function mount(): void
    {
        $url = route('filament.app.pages.insights');

        $this->redirect($url, navigate: FilamentView::hasSpaMode($url));
    }
}
