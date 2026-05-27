<?php

namespace App\Filament\Resources\Organizations\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(['default' => 1, 'lg' => 4])
                    ->schema([
                        Tabs::make('Organization Tabs')
                            ->columnSpan(['default' => 1, 'lg' => 3])
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
                                            ->placeholder('https://example.com'),
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
                                        Select::make('state')
                                            ->label('State')
                                            ->nullable()
                                            ->options([
                                                'Johor' => 'Johor',
                                                'Kedah' => 'Kedah',
                                                'Kelantan' => 'Kelantan',
                                                'Melaka' => 'Melaka',
                                                'Negeri Sembilan' => 'Negeri Sembilan',
                                                'Pahang' => 'Pahang',
                                                'Perak' => 'Perak',
                                                'Perlis' => 'Perlis',
                                                'Pulau Pinang' => 'Pulau Pinang',
                                                'Sabah' => 'Sabah',
                                                'Sarawak' => 'Sarawak',
                                                'Selangor' => 'Selangor',
                                                'Terengganu' => 'Terengganu',
                                                'Wilayah Persekutuan (Kuala Lumpur)' => 'Wilayah Persekutuan (Kuala Lumpur)',
                                                'Wilayah Persekutuan (Labuan)' => 'Wilayah Persekutuan (Labuan)',
                                                'Wilayah Persekutuan (Putrajaya)' => 'Wilayah Persekutuan (Putrajaya)',
                                            ])
                                            ->searchable(),
                                        TextInput::make('postcode')
                                            ->nullable()
                                            ->maxLength(20),
                                        Select::make('country')
                                            ->nullable()
                                            ->default('Malaysia')
                                            ->options([
                                                'Malaysia' => 'Malaysia',
                                                'Brunei' => 'Brunei',
                                                'Cambodia' => 'Cambodia',
                                                'Indonesia' => 'Indonesia',
                                                'Myanmar' => 'Myanmar',
                                                'Philippines' => 'Philippines',
                                                'Singapore' => 'Singapore',
                                                'Thailand' => 'Thailand',
                                                'Vietnam' => 'Vietnam',
                                                'Bangladesh' => 'Bangladesh',
                                                'India' => 'India',
                                                'Pakistan' => 'Pakistan',
                                                'Sri Lanka' => 'Sri Lanka',
                                                'Australia' => 'Australia',
                                                'China' => 'China',
                                                'Japan' => 'Japan',
                                                'South Korea' => 'South Korea',
                                                'Taiwan' => 'Taiwan',
                                                'United Kingdom' => 'United Kingdom',
                                                'United States' => 'United States',
                                                'Other' => 'Other',
                                            ])
                                            ->searchable(),
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

                                Tab::make('Admin')
                                    ->icon('heroicon-o-shield-check')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('fee_collection_method')
                                            ->label('Fee Collection Method')
                                            ->options([
                                                'invoice' => 'Monthly Invoice',
                                                'upfront' => 'Upfront Deduction',
                                            ])
                                            ->default('upfront')
                                            ->helperText('How processing fees are collected from this organization.'),
                                        TextInput::make('processing_fee_override')
                                            ->label('Processing Fee Override (%)')
                                            ->numeric()
                                            ->nullable()
                                            ->minValue(0)
                                            ->maxValue(100)
                                            ->step(0.1)
                                            ->placeholder('Leave blank to use global default')
                                            ->helperText('Override global processing fee for this org. Global default used if blank.'),
                                        Textarea::make('admin_notes')
                                            ->label('Internal Notes')
                                            ->nullable()
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->helperText('Internal notes — not visible to organization.'),
                                    ]),
                            ]),

                        Section::make('Stripe Account')
                            ->columnSpan(['default' => 1, 'lg' => 1])
                            ->icon('heroicon-o-credit-card')
                            ->visible(fn ($record) => $record !== null)
                            ->collapsible()
                            ->schema([
                                Placeholder::make('stripe_status')
                                    ->label('Status')
                                    ->content(function ($record) {
                                        if ($record->stripe_onboarded) {
                                            return new HtmlString('<span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-700 dark:bg-success-900/20 dark:text-success-400"><span class="h-1.5 w-1.5 rounded-full bg-success-500"></span>Connected</span>');
                                        }

                                        return new HtmlString('<span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-800 dark:text-gray-400"><span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>Not connected</span>');
                                    }),

                                Placeholder::make('stripe_account_id')
                                    ->label('Account ID')
                                    ->content(fn ($record) => $record->stripe_account_id
                                        ? new HtmlString('<code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono text-gray-700 dark:bg-gray-800 dark:text-gray-300">'.$record->stripe_account_id.'</code>')
                                        : new HtmlString('<span class="text-sm italic text-gray-400">Not connected</span>')
                                    ),

                                Placeholder::make('stripe_onboarded_at')
                                    ->label('Onboarded')
                                    ->content(fn ($record) => $record->stripe_onboarded_at
                                        ? new HtmlString('<span class="text-sm text-gray-700 dark:text-gray-300">'.$record->stripe_onboarded_at->format('d/m/Y H:i').'</span>')
                                        : new HtmlString('<span class="text-sm italic text-gray-400">—</span>')
                                    ),
                            ]),
                    ]),
            ]);
    }
}
