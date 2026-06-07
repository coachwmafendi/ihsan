<?php

namespace App\Filament\App\Resources\Subscriptions\Pages;

use App\Actions\Stripe\ManageStripeSubscription;
use App\Enums\SubscriptionStatus;
use App\Filament\App\Resources\Subscriptions\SubscriptionResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

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
                    ->icon('heroicon-o-arrow-path')
                    ->extraAttributes(['class' => 'scroll-mt-6'])
                    ->schema([
                        View::make('filament.app.resources.subscriptions.partials.recurring-plan')
                            ->viewData(['record' => $this->getRecord()]),
                    ]),

                Section::make('Personal Information')
                    ->icon('heroicon-o-user')
                    ->extraAttributes(['class' => 'scroll-mt-6'])
                    ->schema([
                        View::make('filament.app.resources.subscriptions.partials.personal-information')
                            ->viewData(['record' => $this->getRecord()]),
                    ]),

                Section::make('Sources')
                    ->icon('heroicon-o-globe-alt')
                    ->extraAttributes(['class' => 'scroll-mt-6'])
                    ->schema([
                        View::make('filament.app.resources.subscriptions.partials.sources')
                            ->viewData(['record' => $this->getRecord()]),
                    ]),

                Section::make('Installments')
                    ->icon('heroicon-o-calendar-days')
                    ->extraAttributes(['class' => 'scroll-mt-6'])
                    ->schema([
                        View::make('filament.app.resources.subscriptions.partials.installments')
                            ->viewData(['record' => $this->getRecord()]),
                    ]),

                Section::make('Receipts')
                    ->icon('heroicon-o-document-text')
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
            ->modalWidth(Width::TwoExtraLarge)
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
            ->modalHeading('Skip installments')
            ->modalWidth(Width::Small)
            ->modalSubmitActionLabel('Confirm')
            ->form([
                Radio::make('skip_type')
                    ->label('')
                    ->options([
                        '1' => 'Skip for 1 month',
                        '3' => 'Skip for 3 months',
                        'custom' => 'Skip for 1–12 months',
                    ])
                    ->default('1')
                    ->live(),

                Select::make('custom_months')
                    ->label('Number of months')
                    ->options(array_combine(range(1, 12), array_map(fn ($n) => "{$n} ".($n === 1 ? 'month' : 'months'), range(1, 12))))
                    ->default(1)
                    ->live()
                    ->hidden(fn (Get $get) => $get('skip_type') !== 'custom'),

                Placeholder::make('next_date_preview')
                    ->label('')
                    ->content(function (Get $get) {
                        $months = $get('skip_type') === 'custom'
                            ? (int) ($get('custom_months') ?: 1)
                            : (int) ($get('skip_type') ?: 1);

                        $nextDate = $this->record->current_period_end
                            ? $this->record->current_period_end->addMonths($months)
                            : now()->addMonths($months);

                        return new HtmlString(
                            '<div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-gray-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-gray-300">'
                            .'The next installment will be made on <strong>'.$nextDate->format('M j, Y, g:i A').'</strong>'
                            .'</div>'
                        );
                    }),
            ])
            ->action(function (array $data) {
                $months = $data['skip_type'] === 'custom'
                    ? (int) ($data['custom_months'] ?: 1)
                    : (int) $data['skip_type'];

                try {
                    app(ManageStripeSubscription::class)->pause($this->record, $months);
                    $this->record->refresh();
                    Notification::make()
                        ->title("Installments skipped for {$months} ".($months === 1 ? 'month' : 'months').'.')
                        ->success()
                        ->send();
                    $this->redirect($this->getResource()::getUrl('view', ['record' => $this->record->public_id ?? $this->record->getKey()]));
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
