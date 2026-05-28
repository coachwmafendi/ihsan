<?php

namespace App\Filament\App\Resources\Subscriptions\Pages;

use App\Actions\Stripe\ManageStripeSubscription;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Filament\App\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    public ?string $paymentClientSecret = null;

    public function getHeading(): string
    {
        $currency = strtoupper($this->record->currency ?? 'MYR');
        $amount = number_format((float) $this->record->amount, 2);

        return "Edit {$currency}{$amount} recurring plan";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeAmount')
                ->label('Change Amount')
                ->icon('heroicon-o-currency-dollar')
                ->color('primary')
                ->modalWidth('sm')
                ->form([
                    TextInput::make('new_amount')
                        ->label('New Amount')
                        ->required()
                        ->numeric()
                        ->prefix(fn () => strtoupper($this->record->currency ?? 'MYR')),
                    Select::make('new_interval')
                        ->label('Interval')
                        ->options(SubscriptionInterval::class)
                        ->default(fn () => $this->record->interval->value),
                ])
                ->action(function (array $data) {
                    try {
                        app(ManageStripeSubscription::class)->changeAmount(
                            $this->record,
                            (float) $data['new_amount'],
                            $data['new_interval']?->value ?? null,
                        );

                        $this->refreshFormData(['amount', 'stripe_price_id']);

                        Notification::make()
                            ->title('Subscription amount updated successfully.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to update amount: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->hidden(fn () => $this->record->status === SubscriptionStatus::Cancelled),
            Action::make('updatePaymentDetails')
                ->label('Update Payment Details')
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->modalHeading('Edit payment details')
                ->modalSubmitAction(false)
                ->modalContent(fn () => view('filament.app.resources.subscriptions.payment-details-modal', ['record' => $this->record]))
                ->action(function (array $data) {})
                ->mountUsing(function () {
                    try {
                        $this->paymentClientSecret = app(ManageStripeSubscription::class)
                            ->createSetupIntent($this->record);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to initialise payment form: '.$e->getMessage())
                            ->danger()
                            ->send();

                        $this->halt();
                    }
                })
                ->hidden(fn () => $this->record->status === SubscriptionStatus::Cancelled),
            Action::make('cancel')
                ->label('Cancel Recurring')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->form([
                    Radio::make('cancel_type')
                        ->label('Cancellation type')
                        ->options([
                            'period_end' => 'At period end — no immediate change, stops after current billing cycle',
                            'immediate' => 'Immediately — stops now, no further charges',
                        ])
                        ->default('period_end'),
                ])
                ->action(function (array $data) {
                    /** @var Subscription $subscription */
                    $subscription = $this->record;

                    try {
                        app(ManageStripeSubscription::class)->cancel(
                            $subscription,
                            immediately: $data['cancel_type'] === 'immediate',
                        );

                        $this->refreshFormData(['status', 'cancel_at_period_end', 'cancelled_at']);

                        Notification::make()
                            ->title($data['cancel_type'] === 'immediate'
                                ? 'Subscription cancelled immediately.'
                                : 'Subscription will cancel at the end of the billing period.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to cancel subscription: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->hidden(fn () => $this->record->status === SubscriptionStatus::Cancelled),
            Action::make('pause')
                ->label('Pause Recurring')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        app(ManageStripeSubscription::class)->pause($this->record);

                        $this->refreshFormData(['paused_until']);

                        Notification::make()
                            ->title('Payment collection paused.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to pause: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->hidden(fn () => $this->record->status !== SubscriptionStatus::Active || $this->record->paused_until !== null),
            Action::make('resume')
                ->label('Resume')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        app(ManageStripeSubscription::class)->resume($this->record);

                        $this->refreshFormData(['paused_until']);

                        Notification::make()
                            ->title('Subscription resumed.')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to resume: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->hidden(fn () => $this->record->paused_until === null),
        ];
    }

    public function savePaymentMethod(string $paymentMethodId): void
    {
        try {
            app(ManageStripeSubscription::class)->updatePaymentMethod(
                $this->record,
                $paymentMethodId,
            );

            Notification::make()
                ->title('Payment method updated successfully.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to update payment method: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
