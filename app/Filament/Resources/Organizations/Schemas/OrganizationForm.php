<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\FileUpload;
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
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'ihsan-org-tabs'])
                    ->tabs([
                        Tab::make('Details')
                            ->icon('heroicon-o-building-office')
                            ->columns(2)
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
                                Select::make('sector')
                                    ->label('Sector')
                                    ->options([
                                        'education' => 'Education',
                                        'healthcare' => 'Healthcare',
                                        'humanitarian' => 'Humanitarian',
                                        'dakwah' => 'Dakwah',
                                        'community_development' => 'Community Development',
                                        'environment' => 'Environment',
                                        'animal_welfare' => 'Animal Welfare',
                                        'orphan_care' => 'Orphan Care',
                                        'masjid' => 'Masjid / Surau',
                                        'others' => 'Others',
                                    ])
                                    ->nullable()
                                    ->searchable(),
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
                            ]),

                        Tab::make('Contact')
                            ->icon('heroicon-o-envelope')
                            ->columns(2)
                            ->schema([
                                TextInput::make('website_url')
                                    ->label('Website URL')
                                    ->required()
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('https://')
                                    ->placeholder('example.com'),
                                TextInput::make('contact_email')
                                    ->label('Contact Email')
                                    ->required()
                                    ->email()
                                    ->maxLength(255),
                                TextInput::make('contact_phone')
                                    ->label('Contact Phone')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('+60')
                                    ->placeholder('123456789'),
                                FileUpload::make('logo_path')
                                    ->label('Logo')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->directory('organizations/logos')
                                    ->maxSize(2048)
                                    ->imageResizeMode('cover')
                                    ->imageCropAspectRatio('1:1')
                                    ->imageResizeTargetWidth('256')
                                    ->imageResizeTargetHeight('256'),
                            ]),

                        Tab::make('Address')
                            ->icon('heroicon-o-map-pin')
                            ->columns(2)
                            ->schema([
                                TextInput::make('address_line_1')
                                    ->label('Address Line 1')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('address_line_2')
                                    ->label('Address Line 2')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('city')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('state')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('postcode')
                                    ->nullable()
                                    ->maxLength(20),
                                TextInput::make('country')
                                    ->nullable()
                                    ->default('Malaysia')
                                    ->maxLength(100),
                            ]),

                        Tab::make('Social')
                            ->icon('heroicon-o-share')
                            ->columns(2)
                            ->schema([
                                TextInput::make('settings.social_facebook')
                                    ->label('Facebook')
                                    ->nullable()
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('facebook.com/')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_instagram')
                                    ->label('Instagram')
                                    ->nullable()
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('instagram.com/')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_twitter')
                                    ->label('X (Twitter)')
                                    ->nullable()
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('x.com/')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_tiktok')
                                    ->label('TikTok')
                                    ->nullable()
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('tiktok.com/@')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_youtube')
                                    ->label('YouTube')
                                    ->nullable()
                                    ->url()
                                    ->maxLength(255)
                                    ->prefix('youtube.com/@')
                                    ->placeholder('channel'),
                            ]),

                        Tab::make('Bank & Payment')
                            ->icon('heroicon-o-building-library')
                            ->columns(2)
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
                                Toggle::make('tax_exempt')
                                    ->label('Tax Exempt')
                                    ->columnSpanFull()
                                    ->helperText('Organisasi ini dikecualikan cukai (e.g., LHDN exemption)'),
                            ]),

                        Tab::make('Stripe')
                            ->icon('heroicon-o-currency-dollar')
                            ->columns(2)
                            ->visible(fn ($record) => $record !== null)
                            ->schema([
                                TextInput::make('stripe_account_id')
                                    ->label('Stripe Account ID')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->helperText('Stripe Connect Express account ID (acct_xxx)')
                                    ->dehydrated(),
                                Toggle::make('stripe_onboarded')
                                    ->label('Stripe Onboarded')
                                    ->disabled()
                                    ->dehydrated()
                                    ->helperText('Updated automatically after successful Stripe onboarding'),
                            ]),
                    ]),
            ]);
    }
}
