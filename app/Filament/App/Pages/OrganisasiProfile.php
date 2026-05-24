<?php

namespace App\Filament\App\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;

class OrganisasiProfile extends Page
{
    protected string $view = 'filament.app.pages.organisasi-profile';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Profil Organisasi';

    protected static ?string $title = 'Profil Organisasi';

    protected static ?int $navigationSort = 998;

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        abort_if(auth()->user()->organization === null, 404);

        $org = auth()->user()->organization;

        $this->form->fill(array_merge($org->toArray(), [
            'settings' => $org->settings ?? [],
        ]));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Maklumat')
                            ->icon('heroicon-o-building-office')
                            ->columns(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nama organisasi')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('ros_rob_number')
                                    ->label('Nombor ROS/ROB')
                                    ->nullable()
                                    ->maxLength(255),
                                RichEditor::make('description')
                                    ->label('Deskripsi')
                                    ->nullable()
                                    ->columnSpanFull(),
                            ]),

                        Tab::make('Hubungi')
                            ->icon('heroicon-o-envelope')
                            ->columns(2)
                            ->schema([
                                TextInput::make('website_url')
                                    ->label('URL laman web')
                                    ->url()
                                    ->nullable()
                                    ->maxLength(255)
                                    ->placeholder('https://example.com'),
                                TextInput::make('contact_email')
                                    ->label('E-mel hubungi')
                                    ->email()
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('contact_phone')
                                    ->label('Telefon hubungi')
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

                        Tab::make('Alamat')
                            ->icon('heroicon-o-map-pin')
                            ->columns(2)
                            ->schema([
                                TextInput::make('address_line_1')
                                    ->label('Alamat 1')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('address_line_2')
                                    ->label('Alamat 2')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('city')
                                    ->label('Bandar')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('state')
                                    ->label('Negeri')
                                    ->nullable()
                                    ->maxLength(255),
                                TextInput::make('postcode')
                                    ->label('Poskod')
                                    ->nullable()
                                    ->maxLength(20),
                                TextInput::make('country')
                                    ->label('Negara')
                                    ->nullable()
                                    ->default('Malaysia')
                                    ->maxLength(100),
                            ]),

                        Tab::make('Sosial')
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
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions())
                        ->key('form-actions'),
                ]),
        ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan')
                ->icon('heroicon-o-check')
                ->submit('save'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $org = auth()->user()->organization;

        $settings = array_merge($org->settings ?? [], $data['settings'] ?? []);

        $org->update(array_merge(
            collect($data)->except('settings')->toArray(),
            ['settings' => $settings],
        ));

        Notification::make()
            ->title('Profil organisasi disimpan')
            ->success()
            ->send();
    }
}
