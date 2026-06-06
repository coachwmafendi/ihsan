<?php

namespace App\Filament\App\Resources\Subscriptions\Pages;

use App\Actions\Stripe\ManageStripeSubscription;
use App\Enums\SubscriptionInterval;
use App\Enums\SubscriptionStatus;
use App\Filament\App\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;

class ViewSubscription extends ViewRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected string $view = 'filament.app.resources.subscriptions.pages.view-subscription';

    public ?string $paymentClientSecret = null;

    public function getHeading(): string
    {
        $currency = strtoupper($this->record->currency ?? 'MYR');
        $amount = number_format((float) $this->record->amount, 2);

        return "{$currency}{$amount} recurring plan";
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Recurring Plan')
                    ->extraAttributes(['class' => 'scroll-mt-6'])
                    ->schema([
                        View::make('filament.app.resources.subscriptions.partials.recurring-plan')
                            ->viewData(['record' => $this->getRecord()]),
                    ]),

                Section::make('Personal Information')
                    ->extraAttributes(['class' => 'scroll-mt-6'])
                    ->schema([
                        Placeholder::make('donor_name')
                            ->label('Name')
                            ->content(fn () => $this->getRecord()->donor?->name ?? '—'),
                        Placeholder::make('donor_email')
                            ->label('Email')
                            ->content(fn () => $this->getRecord()->donor?->email ?? '—'),
                        Placeholder::make('donor_phone')
                            ->label('Phone')
                            ->content(fn () => $this->getRecord()->donor?->phone ?? '—'),
                    ])
                    ->columns(['md' => 2]),

                Section::make('Sources')
                    ->extraAttributes(['class' => 'scroll-mt-6'])
                    ->schema([
                        Placeholder::make('campaign')
                            ->label('Campaign')
                            ->content(fn () => $this->getRecord()->campaign?->title ?? '—'),
                        Placeholder::make('element')
                            ->label('Element')
                            ->content(fn () => $this->getRecord()->element_label ?? '—'),
                    ])
                    ->columns(['md' => 2]),

                Section::make('Installments')
                    ->extraAttributes(['class' => 'scroll-mt-6'])
                    ->schema([
                        View::make('filament.app.resources.subscriptions.partials.installments')
                            ->viewData(['record' => $this->getRecord()]),
                    ]),

                Section::make('Receipts')
                    ->extraAttributes(['class' => 'scroll-mt-6'])
                    ->schema([
                        View::make('filament.app.resources.subscriptions.partials.receipts')
                            ->viewData(['record' => $this->getRecord()]),
                    ]),
            ]);
    }

    public function hasFormWrapper(): bool
    {
        return false;
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function updatePaymentDetailsAction(): Action
    {
        return Action::make('updatePaymentDetails')
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
                    $this->paymentClientSecret = null;
                }
            })
            ->hidden(fn () => $this->record->status === SubscriptionStatus::Cancelled);
    }

    public function skipInstallmentsAction(): Action
    {
        return Action::make('skipInstallments')
            ->label('Skip Installments')
            ->icon('heroicon-o-forward')
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Skip upcoming installments')
            ->modalDescription('This will pause the subscription so no further installments are collected until resumed.')
            ->action(function () {
                try {
                    app(ManageStripeSubscription::class)->pause($this->record);
                    $this->record->refresh();
                    Notification::make()->title('Installments skipped. Subscription paused.')->success()->send();
                } catch (\Exception $e) {
                    Notification::make()->title('Failed to skip installments: '.$e->getMessage())->danger()->send();
                }
            })
            ->hidden(
                fn () => $this->record->status !== SubscriptionStatus::Active
                    || $this->record->paused_until !== null
                    || $this->record->status === SubscriptionStatus::Cancelled
            );
    }

    public function offerPlanUpgradeAction(): Action
    {
        return Action::make('offerPlanUpgrade')
            ->label('Offer Plan Upgrade')
            ->icon('heroicon-o-arrow-trending-up')
            ->color('success')
            ->modalHeading('Offer plan upgrade')
            ->modalDescription('This will prepare an upgrade offer for the donor.')
            ->requiresConfirmation()
            ->action(function () {
                Notification::make()->title('Upgrade offer prepared (placeholder).')->info()->send();
            })
            ->hidden(fn () => $this->record->status === SubscriptionStatus::Cancelled);
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
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
                try {
                    app(ManageStripeSubscription::class)->cancel(
                        $this->record,
                        immediately: $data['cancel_type'] === 'immediate',
                    );
                    $this->record->refresh();
                    Notification::make()
                        ->title($data['cancel_type'] === 'immediate'
                            ? 'Subscription cancelled immediately.'
                            : 'Subscription will cancel at the end of the billing period.')
                        ->success()
                        ->send();
                } catch (\Exception $e) {
                    Notification::make()->title('Failed to cancel: '.$e->getMessage())->danger()->send();
                }
            })
            ->hidden(fn () => $this->record->status === SubscriptionStatus::Cancelled);
    }

    public function pauseAction(): Action
    {
        return Action::make('pause')
            ->label('Pause Recurring')
            ->icon('heroicon-o-pause-circle')
            ->color('warning')
            ->requiresConfirmation()
            ->action(function () {
                try {
                    app(ManageStripeSubscription::class)->pause($this->record);
                    $this->record->refresh();
                    Notification::make()->title('Payment collection paused.')->success()->send();
                } catch (\Exception $e) {
                    Notification::make()->title('Failed to pause: '.$e->getMessage())->danger()->send();
                }
            })
            ->hidden(fn () => $this->record->status !== SubscriptionStatus::Active || $this->record->paused_until !== null);
    }

    public function resumeAction(): Action
    {
        return Action::make('resume')
            ->label('Resume')
            ->icon('heroicon-o-play')
            ->color('success')
            ->requiresConfirmation()
            ->action(function () {
                try {
                    app(ManageStripeSubscription::class)->resume($this->record);
                    $this->record->refresh();
                    Notification::make()->title('Subscription resumed.')->success()->send();
                } catch (\Exception $e) {
                    Notification::make()->title('Failed to resume: '.$e->getMessage())->danger()->send();
                }
            })
            ->hidden(fn () => $this->record->paused_until === null);
    }

    public function saveDetails(float $amount, string $interval, int $billingDay, ?string $cancelAt, bool $coverFee): void
    {
        try {
            $action = app(ManageStripeSubscription::class);

            if ((float) $this->record->amount !== $amount) {
                $action->changeAmount($this->record, $amount, $interval);
            }

            $action->updateDetails($this->record, [
                'interval' => $interval,
                'billing_day' => $billingDay,
                'cancel_at' => $cancelAt,
                'cover_fee' => $coverFee,
            ]);

            $this->record->refresh();

            Notification::make()->title('Subscription details updated.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Failed to update details: '.$e->getMessage())->danger()->send();
        }
    }

    public function savePaymentMethod(string $paymentMethodId): void
    {
        try {
            app(ManageStripeSubscription::class)->updatePaymentMethod($this->record, $paymentMethodId);
            Notification::make()->title('Payment method updated successfully.')->success()->send();
        } catch (\Exception $e) {
            Notification::make()->title('Failed to update payment method: '.$e->getMessage())->danger()->send();
        }
    }
}
