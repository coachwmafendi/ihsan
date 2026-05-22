<?php

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Enums\UserRole;
use App\Notifications\InviteOrganisationAdmin;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Organisation Admins';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('email')
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Invited')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Invite Organisation Admin')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->modalHeading('Invite Organisation Admin')
                    ->modalDescription('They will receive an email to set their password.')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['role'] = UserRole::NgoAdmin;
                        $data['password'] = bcrypt(Str::random(40));

                        return $data;
                    })
                    ->after(function ($record) {
                        $record->notify(new InviteOrganisationAdmin(
                            organizationName: $this->ownerRecord->name,
                        ));

                        Notification::make()
                            ->title('Invitation sent to '.$record->email)
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
