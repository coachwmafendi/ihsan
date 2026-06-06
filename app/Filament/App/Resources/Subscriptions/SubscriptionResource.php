<?php

namespace App\Filament\App\Resources\Subscriptions;

use App\Filament\App\Resources\Subscriptions\Pages\CreateSubscription;
use App\Filament\App\Resources\Subscriptions\Pages\EditSubscription;
use App\Filament\App\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Filament\App\Resources\Subscriptions\Pages\ViewSubscription;
use App\Filament\App\Resources\Subscriptions\Schemas\SubscriptionForm;
use App\Filament\App\Resources\Subscriptions\Tables\SubscriptionsTable;
use App\Models\Subscription;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Recurring';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return 'Subscription #'.$record->id;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['donor.name', 'campaign.title'];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('campaign', fn (Builder $query) => $query
                ->where('organization_id', auth()->user()->organization_id));
    }

    public static function form(Schema $schema): Schema
    {
        return SubscriptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubscriptionsTable::configure($table);
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
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
            'view' => ViewSubscription::route('/{record}'),
        ];
    }
}
