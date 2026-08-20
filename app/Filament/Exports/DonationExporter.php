<?php

namespace App\Filament\Exports;

use App\Models\Donation;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class DonationExporter extends Exporter
{
    protected static ?string $model = Donation::class;

    /**
     * @return array<ExportColumn>
     */
    public static function getColumns(): array
    {
        return [
            ExportColumn::make('invoice_number')
                ->label('Invoice Number')
                ->enabledByDefault(true),
            ExportColumn::make('created_at')
                ->label('Date')
                ->formatStateUsing(fn ($state): string => $state->format('Y-m-d H:i:s'))
                ->enabledByDefault(true),
            ExportColumn::make('public_id')
                ->label('Public ID'),
            ExportColumn::make('donor.name')
                ->label('Donor Name')
                ->enabledByDefault(true),
            ExportColumn::make('donor.email')
                ->label('Donor Email')
                ->enabledByDefault(true),
            ExportColumn::make('donor.phone')
                ->label('Donor Phone'),
            ExportColumn::make('gross_amount')
                ->label('Gross Amount')
                ->formatStateUsing(fn ($state): string => number_format((float) $state, 2))
                ->enabledByDefault(true),
            ExportColumn::make('currency')
                ->label('Currency')
                ->formatStateUsing(fn ($state): string => strtoupper($state))
                ->enabledByDefault(true),
            ExportColumn::make('base_amount')
                ->label('Base Amount (MYR)')
                ->formatStateUsing(fn ($state): string => $state !== null ? number_format((float) $state, 2) : ''),
            ExportColumn::make('stripe_fee')
                ->label('Stripe Fee')
                ->formatStateUsing(fn ($state): string => number_format((float) $state, 2)),
            ExportColumn::make('processing_fee')
                ->label('Processing Fee')
                ->formatStateUsing(fn ($state): string => number_format((float) $state, 2)),
            ExportColumn::make('net_amount')
                ->label('Net Amount')
                ->formatStateUsing(fn ($state): string => number_format((float) $state, 2)),
            ExportColumn::make('donor_fee_covered')
                ->label('Donor Fee Covered')
                ->formatStateUsing(fn ($state): string => number_format((float) $state, 2)),
            ExportColumn::make('payment_method_type')
                ->label('Payment Method Type')
                ->formatStateUsing(fn ($state): string => ucfirst($state)),
            ExportColumn::make('payment_method_brand')
                ->label('Payment Method Brand')
                ->formatStateUsing(fn ($state): ?string => $state ? ucfirst($state) : null),
            ExportColumn::make('payment_method_last4')
                ->label('Card Last 4'),
            ExportColumn::make('status')
                ->label('Status')
                ->formatStateUsing(fn ($state): string => ucfirst($state->value))
                ->enabledByDefault(true),
            ExportColumn::make('type')
                ->label('Type')
                ->formatStateUsing(fn ($state): string => ucfirst($state->value))
                ->enabledByDefault(true),
            ExportColumn::make('subscription.interval')
                ->label('Recurring Interval')
                ->formatStateUsing(fn ($state): ?string => $state ? ucfirst($state->value) : null),
            ExportColumn::make('subscription.payment_count')
                ->label('Payment #')
                ->formatStateUsing(fn ($state): ?string => $state !== null ? (string) $state : null),
            ExportColumn::make('campaign.title')
                ->label('Campaign')
                ->enabledByDefault(true),
            ExportColumn::make('element_label')
                ->label('Element')
                ->formatStateUsing(fn ($state): ?string => $state),
            ExportColumn::make('billing_address_line1')
                ->label('Billing Address Line 1'),
            ExportColumn::make('billing_address_line2')
                ->label('Billing Address Line 2'),
            ExportColumn::make('billing_address_city')
                ->label('Billing City'),
            ExportColumn::make('billing_address_state')
                ->label('Billing State'),
            ExportColumn::make('billing_address_postal_code')
                ->label('Billing Postal Code'),
            ExportColumn::make('billing_country')
                ->label('Billing Country'),
            ExportColumn::make('donor_country')
                ->label('Donor Country')
                ->formatStateUsing(fn ($state): string => strtoupper($state ?? '')),
            ExportColumn::make('device_type')
                ->label('Device Type')
                ->formatStateUsing(fn ($state, Donation $record): string => Donation::deviceLabelFor($state, $record->os) ?? ''),
            ExportColumn::make('is_anonymous')
                ->label('Anonymous')
                ->formatStateUsing(fn ($state): string => $state ? 'Yes' : 'No'),
            ExportColumn::make('donor_message')
                ->label('Message')
                ->formatStateUsing(function (?string $state): ?string {
                    return $state ? "'".$state : null;
                }),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your donation export has completed and '.number_format($export->successful_rows).' ';
        $body .= str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.number_format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
