<?php

namespace App\Filament\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Filament\Actions\Action;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Exceptions\Halt;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Js;
use Throwable;

class EditProfile extends BaseEditProfile
{
    use WithRateLimiting;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('avatar_url')
                    ->avatar()
                    ->label('Avatar')
                    ->imageEditor()
                    ->circleCropper()
                    ->directory('avatars')
                    ->disk('public')
                    ->maxSize(1024)
                    ->columnSpanFull(),
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

    public function save(): void
    {
        try {
            $this->rateLimit(5);
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        $rateLimitingKey = 'filament-edit-profile:'.Filament::auth()->id();

        if (RateLimiter::tooManyAttempts($rateLimitingKey, maxAttempts: 5)) {
            $this->getRateLimitedNotification(new TooManyRequestsException(
                static::class,
                'save',
                request()->ip(),
                RateLimiter::availableIn($rateLimitingKey),
            ))?->send();

            return;
        }

        RateLimiter::hit($rateLimitingKey);

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeSave($data);

            $this->callHook('beforeSave');

            $this->handleRecordUpdate($this->getUser(), $data);

            $this->callHook('afterSave');
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        if (request()->hasSession() && array_key_exists('password', $data)) {
            request()->session()->put([
                'password_hash_'.Filament::getAuthGuard() => $data['password'],
            ]);
        }

        $this->data['password'] = null;
        $this->data['passwordConfirmation'] = null;

        $this->getSavedNotification()?->send();

        if ($redirectUrl = $this->getRedirectUrl()) {
            $this->redirect($redirectUrl, navigate: false);
        }
    }

    public function getRedirectUrl(): string
    {
        $panel = Filament::getCurrentPanel()?->getId();

        return match ($panel) {
            'admin' => route('filament.admin.pages.dashboard'),
            'app' => route('app.insights'),
            default => url('/'),
        };
    }

    protected function getCancelFormAction(): Action
    {
        $panel = Filament::getCurrentPanel()?->getId();

        $url = match ($panel) {
            'admin' => route('filament.admin.pages.dashboard'),
            'app' => route('app.insights'),
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
