<?php

namespace App\Filament\App\Pages;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class ProfilOrganisasi extends Page
{
    protected string $view = 'filament.app.pages.profil-organisasi';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Organisation Profile';

    protected static ?string $title = 'Organisation Profile';

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'profil';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $org = auth()->user()->organization;

        if ($org !== null) {
            $this->form->fill(array_merge($org->toArray(), [
                'settings' => $org->settings ?? [],
            ]));
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Information')
                            ->icon('heroicon-o-building-office')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Organisation name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('ros_rob_number')
                                    ->label('ROS/ROB Number')
                                    ->nullable()
                                    ->maxLength(255),
                                RichEditor::make('description')
                                    ->label('Description')
                                    ->nullable()
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Contact')
                            ->icon('heroicon-o-envelope')
                            ->columns(2)
                            ->schema([
                                TextInput::make('website_url')
                                    ->label('Website URL')
                                    ->url()
                                    ->nullable()
                                    ->maxLength(255)
                                    ->placeholder('https://example.com'),
                                TagsInput::make('settings.allowed_domains')
                                    ->label('Allowed domains')
                                    ->helperText('Organisation website domains. Used as the default domain for new campaigns.')
                                    ->placeholder('Add domain'),
                                TextInput::make('contact_email')
                                    ->label('Contact email')
                                    ->email()
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('contact_phone')
                                    ->label('Contact phone')
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
                                    ->label('Address line 1')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('address_line_2')
                                    ->label('Address line 2')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('city')
                                    ->label('City')
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
                                    ->label('Postcode')
                                    ->nullable()
                                    ->maxLength(20),
                                Select::make('country')
                                    ->label('Country')
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

                        Tab::make('Bank')
                            ->icon('heroicon-o-building-library')
                            ->columns(2)
                            ->schema([
                                TextInput::make('bank_name')
                                    ->label('Bank name')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('bank_account_name')
                                    ->label('Account name')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('bank_account_number')
                                    ->label('Account number')
                                    ->nullable()
                                    ->maxLength(255),
                            ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $org = auth()->user()->organization;

        if ($org === null) {
            return;
        }

        $settings = array_merge($org->settings ?? [], $data['settings'] ?? []);

        $org->update(array_merge(
            collect($data)->except('settings')->toArray(),
            ['settings' => $settings],
        ));

        Notification::make()
            ->title('Organisation profile saved')
            ->success()
            ->send();
    }
}
