<?php

namespace App\Filament\App\Resources\Donors\Pages;

use App\Enums\DonationStatus;
use App\Filament\App\Resources\Donors\DonorResource;
use App\Models\Donation;
use App\Models\Donor;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Fieldset;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;
use Stripe\Customer;
use Stripe\Stripe;

class ViewDonor extends ViewRecord
{
    protected static string $resource = DonorResource::class;

    protected string $view = 'filament.app.resources.donors.pages.view-donor';

    public function getTitle(): string
    {
        return str($this->record->name)->title();
    }

    public function getSubheading(): string|Htmlable|null
    {
        $publicId = e($this->record->public_id ?? $this->record->getKey());
        $lifetime = $this->getLifetimeDonatedMyr();
        $last = $this->getLastDonationDate();
        $lastStr = $last ? $last->format('j M Y') : '—';

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

        return new HtmlString(
            "ID {$publicId} {$copyBtn} · Lifetime donated {$lifetime} · Last donation {$lastStr}"
        );
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getRelationManagers(): array
    {
        return [];
    }

    public function editSupporterAction(): Action
    {
        return Action::make('editSupporter')
            ->label('Edit')
            ->icon('heroicon-o-pencil')
            ->modalHeading('Edit Supporter')
            ->modalSubmitActionLabel('Save Changes')
            ->modalWidth('2xl')
            ->fillForm(fn (Donor $record): array => [
                'name' => $record->name,
                'email' => $record->email,
                'locale' => $record->locale,
                'phone' => $record->phone,
                'address_line1' => $record->address_line1,
                'address_line2' => $record->address_line2,
                'address_city' => $record->address_city,
                'address_state' => $record->address_state,
                'address_postal_code' => $record->address_postal_code,
                'country' => $record->country,
                'update_recurring_plans' => false,
            ])
            ->form([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required(),
                Select::make('locale')
                    ->label('Language')
                    ->options([
                        'en' => 'English',
                        'ms' => 'Malay',
                    ])
                    ->native(false),
                TextInput::make('phone'),
                Fieldset::make('Mailing address')
                    ->schema([
                        TextInput::make('address_line1')
                            ->label('Address line 1'),
                        TextInput::make('address_line2')
                            ->label('Address line 2'),
                        TextInput::make('address_city')
                            ->label('City'),
                        TextInput::make('address_state')
                            ->label('State'),
                        TextInput::make('address_postal_code')
                            ->label('Postal code'),
                        Select::make('country')
                            ->label('Country')
                            ->options([
                                'AF' => 'Afghanistan',
                                'AL' => 'Albania',
                                'DZ' => 'Algeria',
                                'AS' => 'American Samoa',
                                'AD' => 'Andorra',
                                'AO' => 'Angola',
                                'AI' => 'Anguilla',
                                'AQ' => 'Antarctica',
                                'AG' => 'Antigua and Barbuda',
                                'AR' => 'Argentina',
                                'AM' => 'Armenia',
                                'AW' => 'Aruba',
                                'AU' => 'Australia',
                                'AT' => 'Austria',
                                'AZ' => 'Azerbaijan',
                                'BS' => 'Bahamas',
                                'BH' => 'Bahrain',
                                'BD' => 'Bangladesh',
                                'BB' => 'Barbados',
                                'BY' => 'Belarus',
                                'BE' => 'Belgium',
                                'BZ' => 'Belize',
                                'BJ' => 'Benin',
                                'BM' => 'Bermuda',
                                'BT' => 'Bhutan',
                                'BO' => 'Bolivia',
                                'BA' => 'Bosnia and Herzegovina',
                                'BW' => 'Botswana',
                                'BV' => 'Bouvet Island',
                                'BR' => 'Brazil',
                                'IO' => 'British Indian Ocean Territory',
                                'BN' => 'Brunei Darussalam',
                                'BG' => 'Bulgaria',
                                'BF' => 'Burkina Faso',
                                'BI' => 'Burundi',
                                'KH' => 'Cambodia',
                                'CM' => 'Cameroon',
                                'CA' => 'Canada',
                                'CV' => 'Cape Verde',
                                'KY' => 'Cayman Islands',
                                'CF' => 'Central African Republic',
                                'TD' => 'Chad',
                                'CL' => 'Chile',
                                'CN' => 'China',
                                'CX' => 'Christmas Island',
                                'CC' => 'Cocos (Keeling) Islands',
                                'CO' => 'Colombia',
                                'KM' => 'Comoros',
                                'CG' => 'Congo',
                                'CD' => 'Congo, Democratic Republic of the',
                                'CK' => 'Cook Islands',
                                'CR' => 'Costa Rica',
                                'CI' => 'Côte d\'Ivoire',
                                'HR' => 'Croatia',
                                'CU' => 'Cuba',
                                'CY' => 'Cyprus',
                                'CZ' => 'Czech Republic',
                                'DK' => 'Denmark',
                                'DJ' => 'Djibouti',
                                'DM' => 'Dominica',
                                'DO' => 'Dominican Republic',
                                'EC' => 'Ecuador',
                                'EG' => 'Egypt',
                                'SV' => 'El Salvador',
                                'GQ' => 'Equatorial Guinea',
                                'ER' => 'Eritrea',
                                'EE' => 'Estonia',
                                'ET' => 'Ethiopia',
                                'FK' => 'Falkland Islands (Malvinas)',
                                'FO' => 'Faroe Islands',
                                'FJ' => 'Fiji',
                                'FI' => 'Finland',
                                'FR' => 'France',
                                'GF' => 'French Guiana',
                                'PF' => 'French Polynesia',
                                'TF' => 'French Southern Territories',
                                'GA' => 'Gabon',
                                'GM' => 'Gambia',
                                'GE' => 'Georgia',
                                'DE' => 'Germany',
                                'GH' => 'Ghana',
                                'GI' => 'Gibraltar',
                                'GR' => 'Greece',
                                'GL' => 'Greenland',
                                'GD' => 'Grenada',
                                'GP' => 'Guadeloupe',
                                'GU' => 'Guam',
                                'GT' => 'Guatemala',
                                'GG' => 'Guernsey',
                                'GN' => 'Guinea',
                                'GW' => 'Guinea-Bissau',
                                'GY' => 'Guyana',
                                'HT' => 'Haiti',
                                'HM' => 'Heard Island and McDonald Islands',
                                'VA' => 'Holy See (Vatican City State)',
                                'HN' => 'Honduras',
                                'HK' => 'Hong Kong',
                                'HU' => 'Hungary',
                                'IS' => 'Iceland',
                                'IN' => 'India',
                                'ID' => 'Indonesia',
                                'IR' => 'Iran',
                                'IQ' => 'Iraq',
                                'IE' => 'Ireland',
                                'IM' => 'Isle of Man',
                                'IL' => 'Israel',
                                'IT' => 'Italy',
                                'JM' => 'Jamaica',
                                'JP' => 'Japan',
                                'JE' => 'Jersey',
                                'JO' => 'Jordan',
                                'KZ' => 'Kazakhstan',
                                'KE' => 'Kenya',
                                'KI' => 'Kiribati',
                                'KP' => 'Korea, Democratic People\'s Republic of',
                                'KR' => 'Korea, Republic of',
                                'KW' => 'Kuwait',
                                'KG' => 'Kyrgyzstan',
                                'LA' => 'Lao People\'s Democratic Republic',
                                'LV' => 'Latvia',
                                'LB' => 'Lebanon',
                                'LS' => 'Lesotho',
                                'LR' => 'Liberia',
                                'LY' => 'Libyan Arab Jamahiriya',
                                'LI' => 'Liechtenstein',
                                'LT' => 'Lithuania',
                                'LU' => 'Luxembourg',
                                'MO' => 'Macao',
                                'MK' => 'Macedonia, the former Yugoslav Republic of',
                                'MG' => 'Madagascar',
                                'MW' => 'Malawi',
                                'MY' => 'Malaysia',
                                'MV' => 'Maldives',
                                'ML' => 'Mali',
                                'MT' => 'Malta',
                                'MH' => 'Marshall Islands',
                                'MQ' => 'Martinique',
                                'MR' => 'Mauritania',
                                'MU' => 'Mauritius',
                                'YT' => 'Mayotte',
                                'MX' => 'Mexico',
                                'FM' => 'Micronesia, Federated States of',
                                'MD' => 'Moldova, Republic of',
                                'MC' => 'Monaco',
                                'MN' => 'Mongolia',
                                'ME' => 'Montenegro',
                                'MS' => 'Montserrat',
                                'MA' => 'Morocco',
                                'MZ' => 'Mozambique',
                                'MM' => 'Myanmar',
                                'NA' => 'Namibia',
                                'NR' => 'Nauru',
                                'NP' => 'Nepal',
                                'NL' => 'Netherlands',
                                'AN' => 'Netherlands Antilles',
                                'NC' => 'New Caledonia',
                                'NZ' => 'New Zealand',
                                'NI' => 'Nicaragua',
                                'NE' => 'Niger',
                                'NG' => 'Nigeria',
                                'NU' => 'Niue',
                                'NF' => 'Norfolk Island',
                                'MP' => 'Northern Mariana Islands',
                                'NO' => 'Norway',
                                'OM' => 'Oman',
                                'PK' => 'Pakistan',
                                'PW' => 'Palau',
                                'PS' => 'Palestinian Territory, Occupied',
                                'PA' => 'Panama',
                                'PG' => 'Papua New Guinea',
                                'PY' => 'Paraguay',
                                'PE' => 'Peru',
                                'PH' => 'Philippines',
                                'PN' => 'Pitcairn',
                                'PL' => 'Poland',
                                'PT' => 'Portugal',
                                'PR' => 'Puerto Rico',
                                'QA' => 'Qatar',
                                'RE' => 'Réunion',
                                'RO' => 'Romania',
                                'RU' => 'Russian Federation',
                                'RW' => 'Rwanda',
                                'SH' => 'Saint Helena',
                                'KN' => 'Saint Kitts and Nevis',
                                'LC' => 'Saint Lucia',
                                'PM' => 'Saint Pierre and Miquelon',
                                'VC' => 'Saint Vincent and the Grenadines',
                                'WS' => 'Samoa',
                                'SM' => 'San Marino',
                                'ST' => 'Sao Tome and Principe',
                                'SA' => 'Saudi Arabia',
                                'SN' => 'Senegal',
                                'RS' => 'Serbia',
                                'SC' => 'Seychelles',
                                'SL' => 'Sierra Leone',
                                'SG' => 'Singapore',
                                'SK' => 'Slovakia',
                                'SI' => 'Slovenia',
                                'SB' => 'Solomon Islands',
                                'SO' => 'Somalia',
                                'ZA' => 'South Africa',
                                'GS' => 'South Georgia and the South Sandwich Islands',
                                'ES' => 'Spain',
                                'LK' => 'Sri Lanka',
                                'SD' => 'Sudan',
                                'SR' => 'Suriname',
                                'SJ' => 'Svalbard and Jan Mayen',
                                'SZ' => 'Swaziland',
                                'SE' => 'Sweden',
                                'CH' => 'Switzerland',
                                'SY' => 'Syrian Arab Republic',
                                'TW' => 'Taiwan, Province of China',
                                'TJ' => 'Tajikistan',
                                'TZ' => 'Tanzania, United Republic of',
                                'TH' => 'Thailand',
                                'TL' => 'Timor-Leste',
                                'TG' => 'Togo',
                                'TK' => 'Tokelau',
                                'TO' => 'Tonga',
                                'TT' => 'Trinidad and Tobago',
                                'TN' => 'Tunisia',
                                'TR' => 'Turkey',
                                'TM' => 'Turkmenistan',
                                'TC' => 'Turks and Caicos Islands',
                                'TV' => 'Tuvalu',
                                'UG' => 'Uganda',
                                'UA' => 'Ukraine',
                                'AE' => 'United Arab Emirates',
                                'GB' => 'United Kingdom',
                                'US' => 'United States',
                                'UM' => 'United States Minor Outlying Islands',
                                'UY' => 'Uruguay',
                                'UZ' => 'Uzbekistan',
                                'VU' => 'Vanuatu',
                                'VE' => 'Venezuela',
                                'VN' => 'Viet Nam',
                                'VG' => 'Virgin Islands, British',
                                'VI' => 'Virgin Islands, U.S.',
                                'WF' => 'Wallis and Futuna',
                                'EH' => 'Western Sahara',
                                'YE' => 'Yemen',
                                'ZM' => 'Zambia',
                                'ZW' => 'Zimbabwe',
                            ])
                            ->native(false)
                            ->searchable(),
                    ])
                    ->columns(2),
                Checkbox::make('update_recurring_plans')
                    ->label('Update recurring plans'),
            ])
            ->action(function (array $data): void {
                $this->record->update([
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

                if ($this->record->stripe_customer_id) {
                    try {
                        $organization = auth()->user()->organization;
                        Stripe::setApiKey(config('services.stripe.secret'));

                        $stripeOptions = $organization?->stripe_account_id
                            ? ['stripe_account' => $organization->stripe_account_id]
                            : [];

                        Customer::update($this->record->stripe_customer_id, [
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

                Notification::make()
                    ->title('Supporter updated.')
                    ->success()
                    ->send();

                $this->record->refresh();
                $this->redirect(DonorResource::getUrl('view', ['record' => $this->record]), navigate: true);
            });
    }

    public function hasDonationRecords(): bool
    {
        return $this->record->donations()
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->exists();
    }

    public function hasRecurringPlans(): bool
    {
        return $this->record->subscriptions()
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->exists();
    }

    public function hasReceiptRecords(): bool
    {
        return $this->getReceiptDonations()->isNotEmpty();
    }

    /**
     * @return Collection<int, Donation>
     */
    public function getReceiptDonations(): Collection
    {
        return $this->record->donations()
            ->whereNotNull('invoice_number')
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->latest()
            ->get();
    }

    public function getDonorPortalUrl(): string
    {
        $organization = auth()->user()->organization;
        $token = $this->record->generateMagicToken();

        return route('donorportal.magic-login', [$organization, $token]);
    }

    public function getLifetimeDonatedMyr(): string
    {
        $total = $this->record->donations()
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->where('status', DonationStatus::Succeeded)
            ->get()
            ->sum(fn (Donation $d) => (float) ($d->base_amount ?? $d->gross_amount));

        return 'MYR '.number_format($total, 2);
    }

    public function getLastDonationDate(): ?CarbonInterface
    {
        return $this->record->donations()
            ->whereHas('campaign', fn (Builder $query) => $this->scopeToCurrentOrganization($query))
            ->where('status', DonationStatus::Succeeded)
            ->latest()
            ->value('created_at');
    }

    private function scopeToCurrentOrganization(Builder $query): Builder
    {
        return $query->where('organization_id', auth()->user()->organization_id);
    }
}
