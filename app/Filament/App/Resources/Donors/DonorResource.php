<?php

namespace App\Filament\App\Resources\Donors;

use App\Filament\App\Resources\Donors\Pages\CreateDonor;
use App\Filament\App\Resources\Donors\Pages\EditDonor;
use App\Filament\App\Resources\Donors\Pages\ListDonors;
use App\Filament\App\Resources\Donors\Schemas\DonorForm;
use App\Filament\App\Resources\Donors\Tables\DonorsTable;
use App\Models\Donor;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class DonorResource extends Resource
{
    protected static ?string $model = Donor::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Supporters';

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(function (Builder $query) {
                $query
                    ->whereHas('donations.campaign', fn (Builder $campaignQuery) => $campaignQuery
                        ->where('organization_id', auth()->user()->organization_id))
                    ->orWhereHas('subscriptions.campaign', fn (Builder $campaignQuery) => $campaignQuery
                        ->where('organization_id', auth()->user()->organization_id));
            });
    }

    public static function form(Schema $schema): Schema
    {
        return DonorForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DonorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDonors::route('/'),
            'create' => CreateDonor::route('/create'),
            'edit' => EditDonor::route('/{record}/edit'),
        ];
    }
}
