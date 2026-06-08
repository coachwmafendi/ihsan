<?php

namespace App\Filament\App\Resources\Donations\Pages;

use App\Actions\Stripe\RefundDonation;
use App\Enums\DonationStatus;
use App\Filament\App\Resources\Donations\DonationResource;
use App\Models\Campaign;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Fieldset;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Stripe\Customer;
use Stripe\Stripe;

class ViewDonation extends ViewRecord
{
    protected static string $resource = DonationResource::class;

    protected string $view = 'filament.app.resources.donations.pages.view-donation';

    public function getHeading(): string
    {
        $currency = strtoupper($this->record->currency ?? 'MYR');
        $amount = number_format((float) $this->record->gross_amount, 2);

        return "{$currency} {$amount} donation";
    }

    public function getSubheading(): string|Htmlable|null
    {
        $publicId = e($this->record->public_id ?? $this->record->getKey());

        $copyBtn = <<<HTML
            <button
                type="button"
                title="Copy ID"
                onclick="navigator.clipboard.writeText('{$publicId}').then(()=>{const t=this.querySelector('.icon-copy');const c=this.querySelector('.icon-check');t.classList.add('hidden');c.classList.remove('hidden');setTimeout(()=>{t.classList.remove('hidden');c.classList.add('hidden')},1500)})"
                class="inline-flex items-center align-middle text-gray-400 transition hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
            >
                <svg class="icon-copy size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.666 3.888A2.25 2.25 0 0 0 13.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 0 1-.75.75H9a.75.75 0 0 1-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 0 1-2.25 2.25H6.75A2.25 2.25 0 0 1 4.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 0 1 1.927-.184" />
                </svg>
                <svg class="icon-check hidden size-3.5 text-green-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </button>
        HTML;

        $parts = ["ID {$publicId} {$copyBtn}"];

        if ($this->record->currency !== 'myr' && $this->record->base_amount) {
            $myr = number_format((float) $this->record->base_amount, 2);
            $parts[] = "≈ MYR {$myr}";
        }

        return new HtmlString(implode(' · ', $parts));
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

    public function editDonationAction(): Action
    {
        return Action::make('editDonation')
            ->label('Edit')
            ->modalHeading('Edit donation')
            ->modalDescription('These changes will only apply to this specific donation.')
            ->modalWidth(Width::Large)
            ->modalSubmitActionLabel('Save')
            ->form([
                Select::make('campaign_id')
                    ->label('Campaign')
                    ->options(fn () => Campaign::query()
                        ->where('organization_id', auth()->user()->organization_id)
                        ->pluck('title', 'id'))
                    ->default(fn () => $this->record->campaign_id)
                    ->searchable()
                    ->required(),
            ])
            ->action(function (array $data): void {
                $this->record->update(['campaign_id' => $data['campaign_id']]);
                $this->record->refresh();

                Notification::make()->title('Donation updated.')->success()->send();
            });
    }

    public function editSupporterAction(): Action
    {
        return Action::make('editSupporter')
            ->label('Edit')
            ->modalHeading('Edit Supporter')
            ->modalSubmitActionLabel('Save Changes')
            ->modalWidth('2xl')
            ->fillForm(function (): array {
                $donor = $this->record->donor;

                return [
                    'name' => $donor?->name,
                    'email' => $donor?->email,
                    'locale' => $donor?->locale,
                    'phone' => $donor?->phone,
                    'address_line1' => $donor?->address_line1,
                    'address_line2' => $donor?->address_line2,
                    'address_city' => $donor?->address_city,
                    'address_state' => $donor?->address_state,
                    'address_postal_code' => $donor?->address_postal_code,
                    'country' => $donor?->country,
                    'update_recurring_plans' => false,
                ];
            })
            ->form([
                TextInput::make('name')->required(),
                TextInput::make('email')->email()->required(),
                Select::make('locale')
                    ->label('Language')
                    ->options(['en' => 'English', 'ms' => 'Malay'])
                    ->native(false),
                TextInput::make('phone'),
                Fieldset::make('Mailing address')
                    ->schema([
                        TextInput::make('address_line1')->label('Address line 1'),
                        TextInput::make('address_line2')->label('Address line 2'),
                        TextInput::make('address_city')->label('City'),
                        TextInput::make('address_state')->label('State'),
                        TextInput::make('address_postal_code')->label('Postal code'),
                        Select::make('country')
                            ->label('Country')
                            ->options(fn () => collect(\ResourceBundle::getLocales(''))->mapWithKeys(function ($locale) {
                                $region = \Locale::getDisplayRegion('-'.$locale, 'en');

                                return $region ? [strtoupper($locale) => $region] : [];
                            })->filter()->sort()->toArray())
                            ->native(false)
                            ->searchable(),
                    ])
                    ->columns(2),
                Checkbox::make('update_recurring_plans')->label('Update recurring plans'),
            ])
            ->action(function (array $data): void {
                $donor = $this->record->donor;
                if (! $donor) {
                    return;
                }

                $donor->update([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'locale' => $data['locale'] ?: null,
                    'phone' => $data['phone'] ?: null,
                    'address_line1' => $data['address_line1'] ?: null,
                    'address_line2' => $data['address_line2'] ?: null,
                    'address_city' => $data['address_city'] ?: null,
                    'address_state' => $data['address_state'] ?: null,
                    'address_postal_code' => $data['address_postal_code'] ?: null,
                    'country' => $data['country'] ?: null,
                ]);

                if ($donor->stripe_customer_id) {
                    try {
                        $organization = auth()->user()->organization;
                        Stripe::setApiKey(config('services.stripe.secret'));

                        $stripeOptions = $organization?->stripe_account_id
                            ? ['stripe_account' => $organization->stripe_account_id]
                            : [];

                        Customer::update($donor->stripe_customer_id, [
                            'name' => $data['name'],
                            'email' => $data['email'],
                            'phone' => $data['phone'] ?? '',
                            'address' => [
                                'line1' => $data['address_line1'] ?? '',
                                'line2' => $data['address_line2'] ?? '',
                                'city' => $data['address_city'] ?? '',
                                'state' => $data['address_state'] ?? '',
                                'postal_code' => $data['address_postal_code'] ?? '',
                                'country' => $data['country'] ?? '',
                            ],
                        ], $stripeOptions);
                    } catch (\Exception $e) {
                        report($e);
                    }
                }

                $this->record->refresh();

                Notification::make()->title('Supporter updated.')->success()->send();
            });
    }

    public function refundDonation(): void
    {
        if ($this->record->status !== DonationStatus::Succeeded) {
            return;
        }

        try {
            app(RefundDonation::class)->handle($this->record);
            $this->record->refresh();

            Notification::make()
                ->title('Refund successful.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Refund failed: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
