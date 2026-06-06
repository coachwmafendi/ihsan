<?php

namespace App\Filament\App\Resources\Donors\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DonorForm
{
    /** @return array<string, string> */
    private static function countryOptions(): array
    {
        $codes = ['AF', 'AL', 'DZ', 'AD', 'AO', 'AG', 'AR', 'AM', 'AU', 'AT', 'AZ', 'BS', 'BH', 'BD', 'BB', 'BY', 'BE', 'BZ', 'BJ', 'BT', 'BO', 'BA', 'BW', 'BR', 'BN', 'BG', 'BF', 'BI', 'CV', 'KH', 'CM', 'CA', 'CF', 'TD', 'CL', 'CN', 'CO', 'KM', 'CG', 'CD', 'CR', 'HR', 'CU', 'CY', 'CZ', 'DK', 'DJ', 'DM', 'DO', 'EC', 'EG', 'SV', 'GQ', 'ER', 'EE', 'SZ', 'ET', 'FJ', 'FI', 'FR', 'GA', 'GM', 'GE', 'DE', 'GH', 'GR', 'GD', 'GT', 'GN', 'GW', 'GY', 'HT', 'HN', 'HU', 'IS', 'IN', 'ID', 'IR', 'IQ', 'IE', 'IL', 'IT', 'JM', 'JP', 'JO', 'KZ', 'KE', 'KI', 'KP', 'KR', 'KW', 'KG', 'LA', 'LV', 'LB', 'LS', 'LR', 'LY', 'LI', 'LT', 'LU', 'MG', 'MW', 'MY', 'MV', 'ML', 'MT', 'MH', 'MR', 'MU', 'MX', 'FM', 'MD', 'MC', 'MN', 'ME', 'MA', 'MZ', 'MM', 'NA', 'NR', 'NP', 'NL', 'NZ', 'NI', 'NE', 'NG', 'NO', 'OM', 'PK', 'PW', 'PA', 'PG', 'PY', 'PE', 'PH', 'PL', 'PT', 'QA', 'RO', 'RU', 'RW', 'KN', 'LC', 'VC', 'WS', 'SM', 'ST', 'SA', 'SN', 'RS', 'SC', 'SL', 'SG', 'SK', 'SI', 'SB', 'SO', 'ZA', 'SS', 'ES', 'LK', 'SD', 'SR', 'SE', 'CH', 'SY', 'TW', 'TJ', 'TZ', 'TH', 'TL', 'TG', 'TO', 'TT', 'TN', 'TR', 'TM', 'TV', 'UG', 'UA', 'AE', 'GB', 'US', 'UY', 'UZ', 'VU', 'VE', 'VN', 'YE', 'ZM', 'ZW'];

        $countries = [];
        foreach ($codes as $code) {
            $name = \Locale::getDisplayRegion('-'.$code, 'en');
            if ($name && $name !== $code) {
                $countries[$code] = $name;
            }
        }
        asort($countries);

        return $countries;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Supporter Information')
                    ->icon('heroicon-o-user')
                    ->extraAttributes(['id' => 'supporter-information', 'class' => 'scroll-mt-6'])
                    ->columns(['md' => 2])
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(['md' => 2]),
                        TextInput::make('email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(['md' => 2]),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255),
                    ]),
                Section::make('Mailing Address')
                    ->icon('heroicon-o-map-pin')
                    ->extraAttributes(['id' => 'mailing-address', 'class' => 'scroll-mt-6'])
                    ->columns(['md' => 2])
                    ->schema([
                        TextInput::make('address_line1')
                            ->label('Address Line 1')
                            ->maxLength(255)
                            ->columnSpan(['md' => 2]),
                        TextInput::make('address_line2')
                            ->label('Address Line 2')
                            ->maxLength(255)
                            ->columnSpan(['md' => 2]),
                        TextInput::make('address_city')
                            ->label('City')
                            ->maxLength(255),
                        TextInput::make('address_state')
                            ->label('State')
                            ->maxLength(255),
                        TextInput::make('address_postal_code')
                            ->label('Postal Code')
                            ->maxLength(255),
                        Select::make('country')
                            ->label('Country')
                            ->searchable()
                            ->options(self::countryOptions()),
                    ]),
            ]);
    }
}
