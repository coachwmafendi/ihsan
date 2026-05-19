<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Organization Details')
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
                    ])
                    ->columns(2),

                Section::make('Description')
                    ->schema([
                        Textarea::make('description')
                            ->nullable()
                            ->rows(3),
                    ]),

                Section::make('Contact')
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

                Section::make('Status & Approval')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'active' => 'Active',
                                'suspended' => 'Suspended',
                                'rejected' => 'Rejected',
                            ])
                            ->required(),
                        Toggle::make('stripe_onboarded')
                            ->label('Stripe Onboarded'),
                    ])
                    ->columns(2),

                Section::make('Bank Information')
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

                Section::make('Stripe')
                    ->schema([
                        TextInput::make('stripe_account_id')
                            ->label('Stripe Account ID')
                            ->nullable()
                            ->maxLength(255)
                            ->helperText('Stripe Connect Express account ID (acct_xxx)'),
                    ]),
            ]);
    }
}
