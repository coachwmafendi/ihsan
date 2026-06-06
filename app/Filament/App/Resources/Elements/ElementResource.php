<?php

namespace App\Filament\App\Resources\Elements;

use App\Filament\App\Resources\Elements\Pages\CreateElement;
use App\Filament\App\Resources\Elements\Pages\EditElement;
use App\Filament\App\Resources\Elements\Pages\ListElements;
use App\Filament\App\Resources\Elements\Schemas\ElementForm;
use App\Filament\App\Resources\Elements\Tables\ElementsTable;
use App\Models\Element;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ElementResource extends Resource
{
    protected static ?string $model = Element::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationLabel = 'Elements';

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('organization_id', auth()->user()->organization_id);
    }

    public static function form(Schema $schema): Schema
    {
        return ElementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ElementsTable::configure($table);
    }

    public static function getRecordRouteKeyName(): string
    {
        return 'public_id';
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
            'index' => ListElements::route('/'),
            'create' => CreateElement::route('/create'),
            'edit' => EditElement::route('/{record}/edit'),
        ];
    }
}
