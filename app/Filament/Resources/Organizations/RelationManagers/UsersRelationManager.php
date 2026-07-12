<?php

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Enums\UserRole;
use App\Notifications\InviteOrganizationAdmin;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $title = 'Organization Admin';

    protected static ?string $recordTitleAttribute = 'name';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Full name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. Ahmad bin Ali')
                    ->columnSpanFull()
                    ->autocomplete('name'),
                TextInput::make('email')
                    ->label('Email')
                    ->required()
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->placeholder('admin@example.com')
                    ->default(fn () => $this->getOwnerRecord()?->contact_email)
                    ->columnSpanFull()
                    ->autocomplete('email'),
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
                    ->dateTime('d M Y, h:i A', timezone: 'Asia/Kuala_Lumpur')
                    ->formatStateUsing(fn ($state) => $state ? myrTime($state) : '—')
                    ->sortable(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('d M Y, h:i A', timezone: 'Asia/Kuala_Lumpur')
                    ->formatStateUsing(fn ($state) => $state ? myrTime($state) : '—')
                    ->sortable()
                    ->placeholder('Never'),
            ])
            ->filters([
                //
            ])
            ->description('Only one admin login is allowed per organization.')
            ->headerActions([
                CreateAction::make()
                    ->label('Invite Admin')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->modalWidth(Width::Large)
                    ->modalHeading('Invite Organization Admin')
                    ->modalDescription('Fill in the details below. The invitee will receive an email to set their password.')
                    ->modalIcon('heroicon-o-envelope')
                    ->modalIconColor('success')
                    ->modalSubmitActionLabel('Send invite')
                    ->modalCancelActionLabel('Cancel')
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->extraModalWindowAttributes(['class' => 'ihsan-admin-editor-modal'])
                    ->hidden(fn (RelationManager $livewire): bool => $livewire->getOwnerRecord()->users()->exists())
                    ->before(function (RelationManager $livewire, CreateAction $action): void {
                        if ($livewire->getOwnerRecord()->users()->exists()) {
                            Notification::make()
                                ->title('Only one admin login is allowed for this organization.')
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    })
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['role'] = UserRole::NgoAdmin;
                        $data['password'] = bcrypt(Str::random(40));

                        return $data;
                    })
                    ->after(function ($record) {
                        $record->notify(new InviteOrganizationAdmin(
                            organizationName: $this->ownerRecord->name,
                        ));

                        Notification::make()
                            ->title('Invitation sent to '.$record->email)
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->modalWidth(Width::Large)
                    ->modalHeading('Edit Organization Admin')
                    ->modalDescription('Update profile details used for organization panel access.')
                    ->modalIcon('heroicon-o-user-circle')
                    ->modalIconColor('primary')
                    ->modalSubmitActionLabel('Save changes')
                    ->modalCancelActionLabel('Cancel')
                    ->stickyModalHeader()
                    ->stickyModalFooter()
                    ->extraModalWindowAttributes(['class' => 'ihsan-admin-editor-modal']),
                DeleteAction::make()
                    ->modalWidth(Width::Medium)
                    ->modalHeading('Remove Organization Admin')
                    ->modalDescription('This will permanently remove this admin from the organization. They will lose access to the panel.')
                    ->modalIcon('heroicon-o-user-minus')
                    ->modalIconColor('danger')
                    ->modalCancelActionLabel('Cancel')
                    ->extraModalWindowAttributes(['class' => 'ihsan-admin-editor-modal']),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
