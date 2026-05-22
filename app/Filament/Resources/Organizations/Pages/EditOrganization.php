<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Actions\Stripe\CreateConnectAccount;
use App\Enums\OrganizationStatus;
use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrganization extends EditRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reconnect_stripe')
                ->label('Reconnect Stripe')
                ->color('warning')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    try {
                        app(CreateConnectAccount::class)->create($this->record);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Stripe account creation failed')
                            ->body($e->getMessage())
                            ->warning()
                            ->send();

                        return;
                    }

                    $this->refreshFormData(['stripe_account_id']);

                    Notification::make()
                        ->title('Stripe account created')
                        ->success()
                        ->send();
                })
                ->visible(fn () => $this->record->status === OrganizationStatus::Active && $this->record->stripe_account_id === null),

            Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check-circle')
                ->action(function () {
                    try {
                        app(CreateConnectAccount::class)->create($this->record);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Stripe account creation failed')
                            ->body($e->getMessage())
                            ->warning()
                            ->send();

                        return;
                    }

                    $this->record->update([
                        'status' => OrganizationStatus::Active,
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                    ]);

                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title('Organization approved')
                        ->success()
                        ->send();
                })
                ->hidden(fn () => $this->record->status === OrganizationStatus::Active),

            Action::make('suspend')
                ->label('Suspend')
                ->color('warning')
                ->icon('heroicon-o-pause-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => OrganizationStatus::Suspended]);
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title('Organization suspended')
                        ->warning()
                        ->send();
                })
                ->hidden(fn () => $this->record->status !== OrganizationStatus::Active),

            Action::make('reject')
                ->label('Reject')
                ->color('danger')
                ->icon('heroicon-o-x-circle')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['status' => OrganizationStatus::Rejected]);
                    $this->refreshFormData(['status']);

                    Notification::make()
                        ->title('Organization rejected')
                        ->danger()
                        ->send();
                })
                ->hidden(fn () => in_array($this->record->status, [
                    OrganizationStatus::Active,
                    OrganizationStatus::Rejected,
                ])),

            DeleteAction::make(),
        ];
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Organization updated')
            ->success()
            ->send();
    }
}
