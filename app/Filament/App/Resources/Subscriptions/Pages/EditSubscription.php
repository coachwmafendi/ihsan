<?php

namespace App\Filament\App\Resources\Subscriptions\Pages;

use App\Actions\Stripe\ManageStripeSubscription;
use App\Enums\SubscriptionStatus;
use App\Filament\App\Resources\Subscriptions\SubscriptionResource;
use App\Models\Subscription;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
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
            Action::make('updatePaymentDetails')
                ->label('Update Payment Details')
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->modalHeading('Edit payment details')
                ->modalSubmitAction(false)
                ->modalCancelAction(false)
                ->modalContent(fn () => view('filament.app.resources.subscriptions.payment-details-modal', [
                    'record' => $this->record,
                    'clientSecret' => $this->paymentClientSecret,
                ]))
                ->action(function (array $data) {})
                ->mountUsing(function () {
                    try {
                        $this->paymentClientSecret = app(ManageStripeSubscription::class)
                            ->createSetupIntent($this->record);
                    } catch (\Exception $e) {
                        // Still open modal even if setup intent fails
                        $this->paymentClientSecret = null;
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

    public function saveDetails(string $interval, int $billingDay, ?string $cancelAt, bool $coverFee): void
    {
        try {
            app(ManageStripeSubscription::class)->updateDetails($this->record, [
                'interval' => $interval,
                'billing_day' => $billingDay,
                'cancel_at' => $cancelAt,
                'cover_fee' => $coverFee,
            ]);

            $this->refreshFormData(['interval', 'current_period_start', 'current_period_end']);

            Notification::make()
                ->title('Subscription details updated.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Failed to update details: '.$e->getMessage())
                ->danger()
                ->send();
        }
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
