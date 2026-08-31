<?php

namespace App\Filament\Resources\Organizations\Schemas;

use App\Support\ReportingPeriod;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class OrganizationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Organization')
                    ->vertical()
                    ->columnSpanFull()
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
                                        'social_welfare' => 'Social Welfare',
                                        'religion' => 'Religion',
                                        'dakwah' => 'Dakwah',
                                        'community_development' => 'Community Development',
                                        'environment' => 'Environment',
                                        'animal_welfare' => 'Animal Welfare',
                                        'orphan_care' => 'Orphan Care',
                                        'masjid' => 'Masjid / Surau',
                                        'sports_recreation' => 'Sports & Recreation',
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
                                    ->default('+60')
                                    ->placeholder('+60123456789'),
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
                                Select::make('timezone')
                                    ->label('Reporting timezone')
                                    ->helperText('Decides where a day starts and ends in this organization\'s dashboard, reports and exports.')
                                    ->default(ReportingPeriod::DefaultTimezone)
                                    ->required()
                                    ->options(collect(timezone_identifiers_list())
                                        ->mapWithKeys(fn (string $timezone): array => [
                                            $timezone => (new ReportingPeriod($timezone))->label(),
                                        ])
                                        ->all())
                                    ->searchable(),
                            ]),

                        Tab::make('Social')
                            ->icon('heroicon-o-share')
                            ->columns(2)
                            ->schema([
                                TextInput::make('settings.social_facebook')
                                    ->label('Facebook')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('facebook.com/')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_instagram')
                                    ->label('Instagram')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('instagram.com/')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_twitter')
                                    ->label('X (Twitter)')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('x.com/')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_tiktok')
                                    ->label('TikTok')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('tiktok.com/@')
                                    ->placeholder('username'),
                                TextInput::make('settings.social_youtube')
                                    ->label('YouTube')
                                    ->nullable()
                                    ->maxLength(255)
                                    ->prefix('youtube.com/@')
                                    ->placeholder('channel'),
                            ]),

                        Tab::make('Bank Account')
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
                                    ->helperText('This organization is exempt from tax (e.g., LHDN exemption)'),
                            ]),

                        Tab::make('Payment Providers')
                            ->icon('heroicon-o-credit-card')
                            ->columns(1)
                            ->schema([
                                Section::make('Status Overview')
                                    ->columnSpanFull()
                                    ->collapsed(false)
                                    ->visible(fn ($record) => $record !== null)
                                    ->schema([
                                        Text::make(fn ($record) => new HtmlString(
                                            '<div class="divide-y divide-gray-200 dark:divide-gray-700">'.
                                            '<div class="flex items-center justify-between gap-4 py-3">'.
                                            '<div class="flex flex-col gap-1">'.
                                            '<div class="text-sm font-medium text-gray-950 dark:text-white">'.__('Stripe').'</div>'.
                                            '<div class="text-xs text-gray-500 dark:text-gray-400">'.($record?->stripe_account_id ?: 'No account ID').'</div>'.
                                            '</div>'.
                                            '<div class="flex flex-col gap-1 text-right">'.
                                            '<div class="text-sm font-semibold '.($record?->stripe_onboarded ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400').'">'.($record?->stripe_onboarded ? __('Connected') : __('Not connected')).'</div>'.
                                            '<div class="text-xs text-gray-500 dark:text-gray-400">'.($record?->stripe_onboarded_at ? myrTime($record->stripe_onboarded_at, withLabel: true, format: 'd/m/Y H:i') : '—').'</div>'.
                                            '</div>'.
                                            '</div>'.
                                            '<div class="flex items-center justify-between gap-4 py-3">'.
                                            '<div class="flex flex-col gap-1">'.
                                            '<div class="text-sm font-medium text-gray-950 dark:text-white">'.__('CHIP').'</div>'.
                                            '<div class="text-xs text-gray-500 dark:text-gray-400">'.($record?->chip_brand_id ?: 'No brand ID').'</div>'.
                                            '</div>'.
                                            '<div class="flex flex-col gap-1 text-right">'.
                                            '<div class="text-sm font-semibold '.($record?->chip_onboarded ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400').'">'.($record?->chip_onboarded ? __('Onboarded') : __('Not onboarded')).'</div>'.
                                            '</div>'.
                                            '</div>'.
                                            '</div>'
                                        )),
                                    ]),

                                Section::make('Stripe')
                                    ->icon('heroicon-o-credit-card')
                                    ->collapsible()
                                    ->schema([
                                        TextInput::make('stripe_account_id')
                                            ->label('Account ID')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->placeholder('Not connected')
                                            ->prefixIcon(fn ($record) => $record?->stripe_onboarded ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                                            ->prefixIconColor(fn ($record) => $record?->stripe_onboarded ? 'success' : 'danger'),

                                        Text::make(fn ($record) => new HtmlString(
                                            '<span class="block text-sm font-medium text-gray-950 dark:text-white">'.__('Onboarded').'</span>'.
                                            '<span class="block text-sm text-gray-500 dark:text-gray-400 mt-1">'.($record?->stripe_onboarded_at ? myrTime($record->stripe_onboarded_at, withLabel: true, format: 'd/m/Y H:i') : '—').'</span>'
                                        )),

                                        Toggle::make('stripe_onboarded')
                                            ->label('Status')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->onColor('success')
                                            ->offColor('danger')
                                            ->onIcon('heroicon-o-check')
                                            ->offIcon('heroicon-o-x-mark')
                                            ->inline(false),
                                    ]),

                                Section::make('CHIP')
                                    ->icon('heroicon-o-credit-card')
                                    ->collapsible()
                                    ->schema([
                                        TextInput::make('chip_brand_id')
                                            ->label('CHIP Brand ID')
                                            ->nullable()
                                            ->maxLength(255)
                                            ->placeholder('e.g. ORGANIZATION_BRAND_ID')
                                            ->extraInputAttributes(['autocomplete' => 'off']),

                                        TextInput::make('chip_api_key')
                                            ->label('CHIP API Key')
                                            ->nullable()
                                            ->maxLength(255)
                                            ->password()
                                            ->revealable()
                                            ->placeholder('e.g. ORGANIZATION_API_KEY')
                                            ->extraInputAttributes(['autocomplete' => 'new-password']),

                                        TextInput::make('chip_webhook_id')
                                            ->label('CHIP Webhook ID')
                                            ->nullable()
                                            ->maxLength(255)
                                            ->placeholder('e.g. 00000000-0000-0000-0000-000000000000')
                                            ->helperText('From CHIP dashboard → Developers → Webhooks.'),

                                        Textarea::make('chip_webhook_public_key')
                                            ->label('CHIP Webhook Public Key')
                                            ->nullable()
                                            ->rows(4)
                                            ->placeholder('-----BEGIN PUBLIC KEY----- ...')
                                            ->helperText('Used to verify webhook signatures.'),

                                        CheckboxList::make('settings.chip_payment_methods')
                                            ->label('Enabled Payment Methods')
                                            ->options([
                                                'card' => 'Card',
                                                'fpx' => 'FPX',
                                            ])
                                            ->default(['card'])
                                            ->dehydrateStateUsing(fn (?array $state): array => $state ?: ['card'])
                                            ->columns(2),

                                        Text::make(fn ($record) => new HtmlString(
                                            '<span class="block text-sm font-medium text-gray-950 dark:text-white">'.__('Onboarded').'</span>'.
                                            '<span class="block text-sm text-gray-500 dark:text-gray-400 mt-1">'.($record?->chip_onboarded ? 'Yes' : 'No').'</span>'
                                        )),
                                    ]),
                            ]),

                        Tab::make('Billing & Fees')
                            ->icon('heroicon-o-banknotes')
                            ->columns(2)
                            ->schema([
                                Select::make('fee_collection_method')
                                    ->label('Fee Collection Method')
                                    ->options([
                                        'invoice' => 'Monthly Invoice',
                                        'upfront' => 'Upfront Payment',
                                    ])
                                    ->default(config('services.billing.default_fee_collection_method'))
                                    ->selectablePlaceholder(false)
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
            ]);
    }
}
