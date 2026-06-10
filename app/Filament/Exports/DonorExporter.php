<?php

namespace App\Filament\Exports;

use App\Models\Donor;
use Carbon\Carbon;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class DonorExporter extends Exporter
{
    protected static ?string $model = Donor::class;

    /**
     * @return array<ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('public_id')
                ->label('Public ID'),
            ExportColumn::make('name')
                ->label('Name')
                ->enabledByDefault(true),
            ExportColumn::make('email')
                ->label('Email')
                ->enabledByDefault(true),
            ExportColumn::make('phone')
                ->label('Phone'),
            ExportColumn::make('title')
                ->label('Title'),
            ExportColumn::make('occupation')
                ->label('Occupation'),
            ExportColumn::make('address_line1')
                ->label('Address Line 1'),
            ExportColumn::make('address_line2')
                ->label('Address Line 2'),
            ExportColumn::make('address_city')
                ->label('City'),
            ExportColumn::make('address_state')
                ->label('State'),
            ExportColumn::make('address_postal_code')
                ->label('Postal Code'),
            ExportColumn::make('country')
                ->label('Country')
                ->formatStateUsing(fn ($state): string => strtoupper($state ?? '')),
            ExportColumn::make('locale')
                ->label('Locale'),
            ExportColumn::make('lifetime_total_myr')
                ->label('Lifetime Total (MYR)')
                ->formatStateUsing(fn ($state): string => 'MYR '.number_format((float) ($state ?? 0), 2))
                ->enabledByDefault(true),
            ExportColumn::make('donations_min_created_at')
                ->label('First Donation')
                ->formatStateUsing(function ($state): ?string {
                    if (! $state) {
                        return null;
                    }

                    return Carbon::parse($state)->format('Y-m-d');
                }),
            ExportColumn::make('donations_max_created_at')
                ->label('Last Donation')
                ->formatStateUsing(function ($state): ?string {
                    if (! $state) {
                        return null;
                    }

                    return Carbon::parse($state)->format('Y-m-d');
                }),
            ExportColumn::make('created_at')
                ->label('Created At')
                ->formatStateUsing(fn ($state): string => $state->format('Y-m-d H:i:s')),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your donor export has completed and '.number_format($export->successful_rows).' ';
        $body .= str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
