<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Organization Tabs')
                    ->extraAttributes(['class' => 'ihsan-org-tabs'])
                    ->tabs([
                        Tab::make('Details')
                            ->icon('heroicon-o-building-office')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('code')
                                    ->label('Organization Code')
                                    ->disabled()
                                    ->dehydrated()
                                    ->visible(fn ($record) => $record !== null)
                                    ->helperText('Auto-generated on creation'),
                                Select::make('registration_type')
                                    ->options([
                                        'ros' => 'ROS',
                                        'rob' => 'ROB',
                                        'others' => 'Others',
                                    ])
                                    ->required(),
                                TextInput::make('ros_rob_number')
                                    ->label('ROS/ROB Number')
                                    ->nullable()
                                    ->maxLength(255),
                                RichEditor::make('description')
                                    ->nullable()
                                    ->columnSpanFull(),
                                Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'active' => 'Active',
                                        'suspended' => 'Suspended',
                                        'rejected' => 'Rejected',
                                    ])
                                    ->required()
                                    ->default('pending'),
                            ])
                            ->columns(2),

                        Tab::make('Contact')
                            ->icon('heroicon-o-envelope')
                            ->schema([
                                TextInput::make('website_url')
                                    ->label('Website URL')
                                    ->nullable()
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('contact_email')
                                    ->label('Contact Email')
                                    ->nullable()
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('contact_phone')
                                    ->label('Contact Phone')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('logo_path')
                                    ->label('Logo Path')
                                    ->nullable()
                                    ->maxLength(255),
                            ])
                            ->columns(2),

                        Tab::make('Bank & Payment')
                            ->icon('heroicon-o-building-library')
                            ->schema([
                                TextInput::make('bank_account_name')
                                    ->label('Bank Account Name')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('bank_account_number')
                                    ->label('Bank Account Number')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('bank_name')
                                    ->label('Bank Name')
                                    ->nullable()
                                    ->maxLength(255),
                            ])
                            ->columns(2),

                        Tab::make('Stripe')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                TextInput::make('stripe_account_id')
                                    ->label('Stripe Account ID')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->helperText('Stripe Connect Express account ID (acct_xxx)')
                                    ->disabled()
                                    ->dehydrated(),
                                Toggle::make('stripe_onboarded')
                                    ->label('Stripe Onboarded')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Updated automatically after successful Stripe onboarding'),
                            ])
                            ->columns(2),

                    ]),
            ]);
    }
}
