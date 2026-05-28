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
use Stripe\BillingPortal\Session as BillingPortalSession;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;

class EditSubscription extends EditRecord
{
    protected static string $resource = SubscriptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('changeAmount')
                ->label('Change Amount')
                ->icon('heroicon-o-currency-dollar')
                ->color('primary')
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
            Action::make('updatePaymentMethod')
                ->label('Update Payment Method')
                ->icon('heroicon-o-credit-card')
                ->color('info')
                ->requiresConfirmation()
                ->action(function () {
                    try {
                        $subscription = $this->record;
                        $subscription->loadMissing('campaign.organization');

                        Stripe::setApiKey(config('services.stripe.secret'));

                        $org = $subscription->campaign->organization;
                        $stripeOptions = [];
                        if ($org?->stripe_account_id && $org->stripe_onboarded) {
                            $stripeOptions['stripe_account'] = $org->stripe_account_id;
                        }

                        $stripeSubscription = StripeSubscription::retrieve(
                            $subscription->stripe_subscription_id,
                            $stripeOptions,
                        );
                        $customerId = $stripeSubscription->customer;

                        $session = BillingPortalSession::create([
                            'customer' => $customerId,
                            'return_url' => url()->current(),
                        ], $stripeOptions);

                        $this->redirect($session->url);
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to open payment portal: '.$e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->hidden(fn () => $this->record->status === SubscriptionStatus::Cancelled),
            Action::make('cancel')
                ->label('Cancel')
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
                ->label('Pause')
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $data;
    }
}
