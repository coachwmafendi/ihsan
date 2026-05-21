<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Js;

class EditProfile extends BaseEditProfile
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
                $this->getCurrentPasswordFormComponent(),
            ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return parent::getEmailFormComponent()
            ->disabled()
            ->dehydrated(false);
    }

    public function getRedirectUrl(): string
    {
        $panel = Filament::getCurrentPanel()?->getId();

        return match ($panel) {
            'admin' => route('filament.admin.pages.dashboard'),
            'app' => route('filament.app.pages.insights'),
            default => url('/'),
        };
    }

    protected function getCancelFormAction(): Action
    {
        $panel = Filament::getCurrentPanel()?->getId();

        $url = match ($panel) {
            'admin' => route('filament.admin.pages.dashboard'),
            'app' => route('filament.app.pages.insights'),
            default => url('/'),
        };

        return Action::make('back')
            ->label(__('filament-panels::auth/pages/edit-profile.actions.cancel.label'))
            ->alpineClickHandler(
                FilamentView::hasSpaMode()
                    ? 'Livewire.navigate('.Js::from($url).')'
                    : 'window.location.href = '.Js::from($url),
            )
            ->color('gray');
    }
}
