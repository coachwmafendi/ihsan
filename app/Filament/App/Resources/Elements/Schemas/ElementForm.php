<?php

namespace App\Filament\App\Resources\Elements\Schemas;

use App\Enums\ElementType;
use App\Filament\App\Resources\Elements\ElementResource;
use App\Models\Element;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ElementForm
{
    /**
     * @return array<string, mixed>
     */
    public static function defaultConfigForType(BackedEnum|string|null $type): array
    {
        $value = $type instanceof BackedEnum ? (string) $type->value : $type;

        $config = match ($value) {
            ElementType::Button->value => [
                'button_text' => 'Donate Now',
                'button_color' => 'bg-blue-600 hover:bg-blue-700',
                'button_size' => 'text-base px-6 py-3',
                'corner_radius' => 8,
                'button_effect' => 'none',
                'action' => 'checkout_modal',
            ],
            ElementType::FloatingButton->value => [
                'button_text' => 'Donate Now',
                'action' => 'checkout_modal',
                'position' => 'bottom-right',
                'color' => 'campaign',
                'button_effect' => 'none',
                'icon' => 'heart',
                'shape' => 'pill',
                'size' => 'Medium',
                'visible_desktop' => true,
                'visible_mobile' => true,
            ],
            ElementType::Form->value => [
                'template' => 'secure-donation',
                'title' => 'Your most generous donation',
                'text_color' => '#212830',
                'background_color' => '#FFFFFF',
                'icon_color' => '#FF435A',
                'border_size' => 2,
                'border_radius' => 6,
                'border_color' => '#DEDFF3',
                'show_shadow' => false,
                'show_suggested' => true,
                'show_amount_input' => true,
                'allow_monthly' => true,
                'show_dedication' => true,
                'submit_text' => 'Donate and Support',
                'button_effect' => 'none',
            ],
            ElementType::Popup->value => [
                'title' => 'Support Our Campaign Today',
                'message' => null,
                'button_text' => 'Donate Now',
                'action' => 'checkout_modal',
                'trigger' => 'after_delay',
                'delay' => 8,
                'frequency' => 'once_per_day',
                'visibility' => 'desktop_mobile',
                'layout' => 'simple',
                'image' => null,
                'color' => 'campaign',
                'button_effect' => 'none',
            ],
            'qr_code' => [
                'size' => 'Medium',
                'label' => 'Scan to donate',
                'alignment' => 'center',
                'margin_top' => 0,
                'margin_bottom' => 0,
            ],
            'link' => [
                'text' => 'Donate Now',
                'url' => '',
                'action' => 'checkout_modal',
                'style' => 'button',
                'color' => 'campaign',
                'button_effect' => 'none',
                'size' => 'Medium',
                'alignment' => 'left',
            ],
            default => [],
        };

        return $config;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public static function configure(Schema $schema, array $options = []): Schema
    {
        $hideCampaign = $options['hide_campaign'] ?? false;
        $hideSubmit = $options['hide_submit'] ?? false;

        return $schema
            ->components([
                Section::make('Element Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live()
                            ->placeholder('e.g. Main Donation Form'),
                        Select::make('type')
                            ->required()
                            ->live()
                            ->options([
                                'button' => 'Button',
                                'floating_button' => 'Floating Button',
                                'form' => 'Form',
                                'popup' => 'Popup',
                                'qr_code' => 'QR Code',
                                'link' => 'Link',
                            ])
                            ->placeholder('Select type'),
                        Select::make('campaign_id')
                            ->label('Campaign')
                            ->relationship('campaign', 'title', modifyQueryUsing: fn (Builder $query) => $query
                                ->where('organization_id', auth()->user()->organization_id))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->placeholder('Optional: link to a campaign')
                            ->hidden($hideCampaign),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inlineLabel(false)
                            ->helperText('Enable or disable this element'),

                        View::make('filament.forms.components.element-embed-snippet')
                            ->viewData(function (Get $get, ?Element $record): array {
                                $recordConfig = $record?->config ?? [];
                                $liveConfig = $get('config') ?? [];
                                $type = $get('type') ?? $record?->type?->value;

                                return [
                                    'element' => $record,
                                    'liveConfig' => array_merge($recordConfig, $liveConfig),
                                    'liveType' => $type,
                                ];
                            })
                            ->visible(fn ($record) => $record !== null)
                            ->reactive()
                            ->columnSpanFull(),
                    ]),
                Section::make('Button')
                    ->description('Configure your inline donate button')
                    ->columnSpanFull()
                    ->icon('heroicon-m-hand-raised')
                    ->visible(fn (Get $get): bool => self::selectedType($get('type')) === ElementType::Button->value)
                    ->schema([
                        Grid::make()
                            ->statePath('config')
                            ->columns(2)
                            ->schema([
                                TextInput::make('button_text')
                                    ->label('Button Text')
                                    ->default('Donate Now')
                                    ->required()
                                    ->live(),
                                Select::make('action')
                                    ->label('Action')
                                    ->options([
                                        'campaign_page' => 'Open campaign page',
                                        'checkout_modal' => 'Open checkout modal',
                                    ])
                                    ->default('checkout_modal')
                                    ->live()
                                    ->native(false),
                                Select::make('button_effect')
                                    ->label('Button Effect')
                                    ->options([
                                        'none' => 'None (solid color)',
                                        'gradient_teal_green' => 'Gradient — Teal & Green',
                                        'gradient_blue_purple' => 'Gradient — Blue & Purple',
                                        'gradient_orange_red' => 'Gradient — Orange & Red',
                                        'gradient_rose_pink' => 'Gradient — Rose & Pink',
                                        'gradient_amber_orange' => 'Gradient — Amber & Orange',
                                        'gradient_cyan_blue' => 'Gradient — Cyan & Blue',
                                        'gradient_emerald_teal' => 'Gradient — Emerald & Teal',
                                        'gradient_indigo_purple' => 'Gradient — Indigo & Purple',
                                        'gradient_gold_amber' => 'Gradient — Gold & Amber',
                                        'gradient_pink_purple' => 'Gradient — Pink & Purple',
                                    ])
                                    ->default('none')
                                    ->live()
                                    ->native(false),
                                Select::make('button_color')
                                    ->label('Button Color')
                                    ->options([
                                        'bg-blue-600 hover:bg-blue-700' => 'Blue',
                                        'bg-teal-600 hover:bg-teal-700' => 'Teal',
                                        'bg-green-600 hover:bg-green-700' => 'Green',
                                        'bg-orange-600 hover:bg-orange-700' => 'Orange',
                                        'bg-red-600 hover:bg-red-700' => 'Red',
                                        'bg-purple-600 hover:bg-purple-700' => 'Purple',
                                        'bg-gray-900 hover:bg-gray-800' => 'Dark',
                                    ])
                                    ->default('bg-blue-600 hover:bg-blue-700')
                                    ->live()
                                    ->native(false)
                                    ->disabled(fn (Get $get): bool => ($get('button_effect') ?? 'none') !== 'none'),
                                Select::make('button_size')
                                    ->label('Button Size')
                                    ->options([
                                        'text-sm px-4 py-2' => 'Small',
                                        'text-base px-6 py-3' => 'Medium',
                                        'text-lg px-8 py-4' => 'Large',
                                    ])
                                    ->default('text-base px-6 py-3')
                                    ->live()
                                    ->native(false),
                                TextInput::make('corner_radius')
                                    ->label('Corner Radius')
                                    ->numeric()
                                    ->default(8)
                                    ->suffix('px')
                                    ->live(),
                            ]),
                        View::make('filament.forms.components.element-preview')
                            ->columnSpanFull()
                            ->viewData(fn (Get $get): array => [
                                'type' => $get('type'),
                                'config' => self::previewConfig($get),
                            ]),
                    ]),
                Section::make('Floating Button')
                    ->description('A fixed-position button on the edge of the page')
                    ->columnSpanFull()
                    ->icon('heroicon-m-arrows-pointing-out')
                    ->visible(fn (Get $get): bool => self::selectedType($get('type')) === ElementType::FloatingButton->value)
                    ->schema([
                        Grid::make()
                            ->statePath('config')
                            ->columns(2)
                            ->schema([
                                Section::make('Content')
                                    ->compact()
                                    ->schema([
                                        TextInput::make('button_text')
                                            ->label('Button text')
                                            ->default('Donate Now')
                                            ->required()
                                            ->live(),
                                        Select::make('action')
                                            ->label('Action')
                                            ->options([
                                                'campaign_page' => 'Open campaign page',
                                                'checkout_modal' => 'Open checkout modal',
                                            ])
                                            ->default('checkout_modal')
                                            ->live()
                                            ->native(false),
                                        Select::make('icon')
                                            ->label('Icon')
                                            ->options([
                                                'heart' => 'Heart',
                                                'hand' => 'Hand',
                                                'star' => 'Star',
                                                'gift' => 'Gift',
                                                'plus' => 'Plus',
                                            ])
                                            ->default('heart')
                                            ->live()
                                            ->native(false),
                                    ]),
                                Section::make('Position & Size')
                                    ->compact()
                                    ->schema([
                                        Select::make('position')
                                            ->label('Position')
                                            ->options([
                                                'bottom-right' => 'Bottom right',
                                                'bottom-left' => 'Bottom left',
                                                'top-right' => 'Top right',
                                                'top-left' => 'Top left',
                                                'vertical-left-center' => 'Vertical left center',
                                                'vertical-right-center' => 'Vertical right center',
                                            ])
                                            ->default('bottom-right')
                                            ->live()
                                            ->native(false),
                                        Select::make('size')
                                            ->label('Size')
                                            ->options([
                                                'Small' => 'Small',
                                                'Medium' => 'Medium',
                                                'Large' => 'Large',
                                            ])
                                            ->default('Medium')
                                            ->live()
                                            ->native(false),
                                        Select::make('shape')
                                            ->label('Shape')
                                            ->options([
                                                'pill' => 'Pill',
                                                'circle' => 'Circle',
                                                'square' => 'Square',
                                                'rounded' => 'Rounded',
                                            ])
                                            ->default('pill')
                                            ->live()
                                            ->native(false),
                                    ]),
                                Section::make('Color')
                                    ->compact()
                                    ->schema([
                                        Select::make('button_effect')
                                            ->label('Button Effect')
                                            ->options([
                                                'none' => 'None (solid color)',
                                                'gradient_teal_green' => 'Gradient — Teal & Green',
                                                'gradient_blue_purple' => 'Gradient — Blue & Purple',
                                                'gradient_orange_red' => 'Gradient — Orange & Red',
                                                'gradient_rose_pink' => 'Gradient — Rose & Pink',
                                                'gradient_amber_orange' => 'Gradient — Amber & Orange',
                                                'gradient_cyan_blue' => 'Gradient — Cyan & Blue',
                                                'gradient_emerald_teal' => 'Gradient — Emerald & Teal',
                                                'gradient_indigo_purple' => 'Gradient — Indigo & Purple',
                                                'gradient_gold_amber' => 'Gradient — Gold & Amber',
                                                'gradient_pink_purple' => 'Gradient — Pink & Purple',
                                            ])
                                            ->default('none')
                                            ->live()
                                            ->native(false),
                                        Select::make('color')
                                            ->label('Color scheme')
                                            ->options([
                                                'campaign' => 'Use campaign color',
                                                'blue' => 'Blue',
                                                'teal' => 'Teal',
                                                'green' => 'Green',
                                                'orange' => 'Orange',
                                                'red' => 'Red',
                                                'purple' => 'Purple',
                                                'dark' => 'Dark',
                                            ])
                                            ->default('campaign')
                                            ->live()
                                            ->native(false)
                                            ->disabled(fn (Get $get): bool => ($get('button_effect') ?? 'none') !== 'none'),
                                        Toggle::make('visible_desktop')
                                            ->label('Show on desktop')
                                            ->default(true)
                                            ->live(),
                                        Toggle::make('visible_mobile')
                                            ->label('Show on mobile')
                                            ->default(true)
                                            ->live(),
                                    ]),
                            ]),
                        View::make('filament.forms.components.element-preview')
                            ->columnSpanFull()
                            ->viewData(fn (Get $get): array => [
                                'type' => $get('type'),
                                'config' => self::previewConfig($get),
                            ]),
                    ]),
                Grid::make([
                    'default' => 1,
                    'xl' => 12,
                ])
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'ihsan-builder-shell'])
                    ->visible(fn (Get $get): bool => $get('type') === ElementType::Popup->value)
                    ->schema([
                        Grid::make(1)
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 7,
                            ])
                            ->statePath('config')
                            ->extraAttributes(['class' => 'ihsan-builder-editor space-y-4'])
                            ->schema([
                                Section::make('Content & Appearance')
                                    ->columns(2)
                                    ->icon('heroicon-m-document-text')
                                    ->schema([
                                        FileUpload::make('image')
                                            ->label('Image')
                                            ->image()
                                            ->disk('public')
                                            ->directory('elements/popups')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif'])
                                            ->helperText('Saiz ideal: 960 x 540 px. JPG, PNG, WebP, AVIF.')
                                            ->live()
                                            ->columnSpanFull(),
                                        TextInput::make('title')
                                            ->label('Title')
                                            ->required()
                                            ->live()
                                            ->columnSpanFull(),
                                        Textarea::make('message')
                                            ->label('Message')
                                            ->rows(3)
                                            ->live()
                                            ->columnSpanFull(),
                                        TextInput::make('button_text')
                                            ->label('Button text')
                                            ->required()
                                            ->live(),
                                        Select::make('action')
                                            ->label('Action')
                                            ->options([
                                                'campaign_page' => 'Open campaign page',
                                                'checkout_modal' => 'Open checkout modal',
                                            ])
                                            ->default('checkout_modal')
                                            ->live()
                                            ->native(false),
                                        Select::make('layout')
                                            ->label('Layout')
                                            ->options([
                                                'simple' => 'Simple',
                                                'full' => 'Full',
                                            ])
                                            ->default('simple')
                                            ->live()
                                            ->native(false),
                                        Select::make('color')
                                            ->label('Color')
                                            ->options([
                                                'campaign' => 'Use campaign color',
                                                'blue' => 'Blue',
                                                'teal' => 'Teal',
                                                'green' => 'Green',
                                                'orange' => 'Orange',
                                                'red' => 'Red',
                                                'purple' => 'Purple',
                                                'dark' => 'Dark',
                                            ])
                                            ->default('campaign')
                                            ->live()
                                            ->native(false),
                                        Select::make('button_effect')
                                            ->label('Button Effect')
                                            ->options([
                                                'none' => 'None (solid color)',
                                                'gradient_teal_green' => 'Gradient — Teal & Green',
                                                'gradient_blue_purple' => 'Gradient — Blue & Purple',
                                                'gradient_orange_red' => 'Gradient — Orange & Red',
                                                'gradient_rose_pink' => 'Gradient — Rose & Pink',
                                                'gradient_amber_orange' => 'Gradient — Amber & Orange',
                                                'gradient_cyan_blue' => 'Gradient — Cyan & Blue',
                                                'gradient_emerald_teal' => 'Gradient — Emerald & Teal',
                                                'gradient_indigo_purple' => 'Gradient — Indigo & Purple',
                                                'gradient_gold_amber' => 'Gradient — Gold & Amber',
                                                'gradient_pink_purple' => 'Gradient — Pink & Purple',
                                            ])
                                            ->default('none')
                                            ->helperText('Overrides the button color above')
                                            ->live()
                                            ->native(false),
                                    ]),
                                Section::make('Display Rules')
                                    ->columns(2)
                                    ->icon('heroicon-m-eye')
                                    ->schema([
                                        Select::make('trigger')
                                            ->label('Trigger')
                                            ->options([
                                                'immediately' => 'Immediately',
                                                'after_delay' => 'After delay',
                                                'on_scroll' => 'On scroll',
                                                'exit_intent' => 'Exit intent',
                                            ])
                                            ->default('after_delay')
                                            ->live()
                                            ->native(false),
                                        TextInput::make('delay')
                                            ->label('Delay (seconds)')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(8)
                                            ->live()
                                            ->visible(fn (Get $get): bool => $get('trigger') === 'after_delay'),
                                        Select::make('frequency')
                                            ->label('Frequency')
                                            ->options([
                                                'once' => 'Once only',
                                                'once_per_session' => 'Once per session',
                                                'once_per_day' => 'Once per day',
                                                'every_visit' => 'Every visit',
                                            ])
                                            ->default('once_per_day')
                                            ->live()
                                            ->native(false),
                                        Select::make('visibility')
                                            ->label('Visibility')
                                            ->options([
                                                'desktop_mobile' => 'Desktop & mobile',
                                                'desktop_only' => 'Desktop only',
                                                'mobile_only' => 'Mobile only',
                                            ])
                                            ->default('desktop_mobile')
                                            ->live()
                                            ->native(false),
                                    ]),
                            ]),
                        Section::make('Live Preview')
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 5,
                            ])
                            ->extraAttributes(['class' => 'ihsan-builder-preview xl:sticky xl:top-6 xl:self-start'])
                            ->schema([
                                View::make('filament.forms.components.element-preview')
                                    ->viewData(fn (Get $get): array => [
                                        'type' => $get('type'),
                                        'config' => self::previewConfig($get),
                                    ]),
                            ]),
                    ]),
                Grid::make([
                    'default' => 1,
                    'xl' => 12,
                ])
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'ihsan-builder-shell'])
                    ->visible(fn (Get $get): bool => $get('type') === ElementType::Form->value)
                    ->schema([
                        Tabs::make('Donation Form Workbench')
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 7,
                            ])
                            ->extraAttributes(['class' => 'ihsan-builder-editor'])
                            ->statePath('config')
                            ->tabs([
                                Tab::make('Form')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Title')
                                            ->default('Your most generous donation')
                                            ->required()
                                            ->live(),
                                        TextInput::make('submit_text')
                                            ->label('Button label')
                                            ->default('Donate and Support')
                                            ->required()
                                            ->live(),
                                        Select::make('button_effect')
                                            ->label('Button Effect')
                                            ->options([
                                                'none' => 'None (teal)',
                                                'gradient_teal_green' => 'Gradient — Teal & Green',
                                                'gradient_blue_purple' => 'Gradient — Blue & Purple',
                                                'gradient_orange_red' => 'Gradient — Orange & Red',
                                                'gradient_rose_pink' => 'Gradient — Rose & Pink',
                                                'gradient_amber_orange' => 'Gradient — Amber & Orange',
                                                'gradient_cyan_blue' => 'Gradient — Cyan & Blue',
                                                'gradient_emerald_teal' => 'Gradient — Emerald & Teal',
                                                'gradient_indigo_purple' => 'Gradient — Indigo & Purple',
                                                'gradient_gold_amber' => 'Gradient — Gold & Amber',
                                                'gradient_pink_purple' => 'Gradient — Pink & Purple',
                                            ])
                                            ->default('none')
                                            ->live()
                                            ->native(false),
                                        Select::make('default_frequency')
                                            ->label('Default frequency')
                                            ->options([
                                                'one_time' => 'One-time',
                                                'monthly' => 'Monthly',
                                            ])
                                            ->default('monthly')
                                            ->live()
                                            ->columnSpan(1),
                                        TextInput::make('default_amount')
                                            ->label('Default amount (RM)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(30)
                                            ->live()
                                            ->columnSpan(1),
                                        Placeholder::make('amounts_note')
                                            ->hiddenLabel()
                                            ->content(new HtmlString('<p class="text-sm text-zinc-500">Suggested donation amounts are controlled from <strong>campaign settings</strong>.</p>'))
                                            ->columnSpanFull(),
                                        Toggle::make('allow_monthly')
                                            ->label('Allow monthly donations')
                                            ->default(true)
                                            ->live(),
                                        Toggle::make('allow_cover_fee')
                                            ->label('Allow donors to cover processing fee')
                                            ->helperText('Donors will see a pre-checked option to cover the Stripe processing fee (~3% + RM 0.50).')
                                            ->default(true)
                                            ->live(),
                                        Toggle::make('show_suggested')
                                            ->label('Show suggested amounts')
                                            ->default(true)
                                            ->live(),
                                        Toggle::make('show_amount_input')
                                            ->label('Show amount input')
                                            ->default(true)
                                            ->live(),
                                    ]),
                            ]),
                        Section::make('Live Preview')
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 5,
                            ])
                            ->extraAttributes(['class' => 'ihsan-builder-preview xl:sticky xl:top-6 xl:self-start'])
                            ->schema([
                                View::make('filament.forms.components.element-preview')
                                    ->viewData(fn (Get $get): array => [
                                        'type' => $get('type'),
                                        'name' => $get('name'),
                                        'config' => self::previewConfig($get),
                                    ]),
                            ]),
                    ]),
                Section::make('QR Code')
                    ->description('Configure a scannable QR code that links to your donation page')
                    ->columnSpanFull()
                    ->icon('heroicon-m-qr-code')
                    ->visible(fn (Get $get): bool => self::selectedType($get('type')) === ElementType::QrCode->value)
                    ->schema([
                        Grid::make()
                            ->statePath('config')
                            ->columns(2)
                            ->schema([
                                Select::make('size')
                                    ->label('Size')
                                    ->options([
                                        'Small' => 'Small (150px)',
                                        'Medium' => 'Medium (200px)',
                                        'Large' => 'Large (250px)',
                                        'Extra Large' => 'Extra Large (300px)',
                                    ])
                                    ->default(200)
                                    ->live()
                                    ->native(false),
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('center')
                                    ->live()
                                    ->native(false),
                                TextInput::make('label')
                                    ->label('Label text')
                                    ->default('Scan to donate')
                                    ->placeholder('Text shown below the QR code')
                                    ->live(),
                                TextInput::make('margin_top')
                                    ->label('Top margin')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('px')
                                    ->live(),
                                TextInput::make('margin_bottom')
                                    ->label('Bottom margin')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('px')
                                    ->live(),
                            ]),
                        View::make('filament.forms.components.element-preview')
                            ->columnSpanFull()
                            ->viewData(fn (Get $get): array => [
                                'type' => $get('type'),
                                'config' => self::previewConfig($get),
                            ]),
                    ]),
                Section::make('Link')
                    ->description('A simple text link to your donation page')
                    ->columnSpanFull()
                    ->icon('heroicon-m-link')
                    ->visible(fn (Get $get): bool => self::selectedType($get('type')) === ElementType::Link->value)
                    ->schema([
                        Grid::make()
                            ->statePath('config')
                            ->columns(2)
                            ->schema([
                                TextInput::make('text')
                                    ->label('Link text')
                                    ->default('Donate Now')
                                    ->required()
                                    ->live(),
                                Select::make('action')
                                    ->label('Action')
                                    ->options([
                                        'checkout_modal' => 'Open checkout modal',
                                        'campaign_page' => 'Open campaign page',
                                    ])
                                    ->default('checkout_modal')
                                    ->live()
                                    ->native(false),
                                TextInput::make('url')
                                    ->label('Custom URL (optional)')
                                    ->url()
                                    ->placeholder('https://')
                                    ->helperText('Leave empty to use campaign donation page')
                                    ->visible(fn (Get $get): bool => $get('action') !== 'checkout_modal')
                                    ->live(),
                                Select::make('style')
                                    ->label('Style')
                                    ->options([
                                        'button' => 'Button',
                                        'text' => 'Plain text link',
                                    ])
                                    ->default('button')
                                    ->live()
                                    ->native(false),
                                Select::make('color')
                                    ->label('Color')
                                    ->options([
                                        'campaign' => 'Campaign color',
                                        'blue' => 'Blue',
                                        'teal' => 'Teal',
                                        'green' => 'Green',
                                        'orange' => 'Orange',
                                        'red' => 'Red',
                                        'purple' => 'Purple',
                                        'dark' => 'Dark',
                                    ])
                                    ->default('campaign')
                                    ->live()
                                    ->native(false)
                                    ->disabled(fn (Get $get): bool => ($get('button_effect') ?? 'none') !== 'none'),
                                Select::make('button_effect')
                                    ->label('Button Effect')
                                    ->options([
                                        'none' => 'None (solid color)',
                                        'gradient_teal_green' => 'Gradient — Teal & Green',
                                        'gradient_blue_purple' => 'Gradient — Blue & Purple',
                                        'gradient_orange_red' => 'Gradient — Orange & Red',
                                        'gradient_rose_pink' => 'Gradient — Rose & Pink',
                                        'gradient_amber_orange' => 'Gradient — Amber & Orange',
                                        'gradient_cyan_blue' => 'Gradient — Cyan & Blue',
                                        'gradient_emerald_teal' => 'Gradient — Emerald & Teal',
                                        'gradient_indigo_purple' => 'Gradient — Indigo & Purple',
                                        'gradient_gold_amber' => 'Gradient — Gold & Amber',
                                        'gradient_pink_purple' => 'Gradient — Pink & Purple',
                                    ])
                                    ->default('none')
                                    ->live()
                                    ->native(false)
                                    ->visible(fn (Get $get): bool => ($get('style') ?? 'button') === 'button'),
                                Select::make('size')
                                    ->label('Size')
                                    ->options([
                                        'Small' => 'Small',
                                        'Medium' => 'Medium',
                                        'Large' => 'Large',
                                    ])
                                    ->default('Medium')
                                    ->live()
                                    ->native(false),
                                Select::make('alignment')
                                    ->label('Alignment')
                                    ->options([
                                        'left' => 'Left',
                                        'center' => 'Center',
                                        'right' => 'Right',
                                    ])
                                    ->default('left')
                                    ->live()
                                    ->native(false),
                            ]),
                        View::make('filament.forms.components.element-preview')
                            ->columnSpanFull()
                            ->viewData(fn (Get $get): array => [
                                'type' => $get('type'),
                                'config' => self::previewConfig($get),
                            ]),
                    ]),
                View::make('filament.forms.components.element-form-submit')
                    ->visible(! $hideSubmit)
                    ->columnSpanFull()
                    ->viewData(fn (?Element $record): array => [
                        'label' => $record ? 'Save changes' : 'Create element',
                        'cancelUrl' => ElementResource::getUrl('index'),
                    ]),
            ]);
    }

    private static function selectedType(BackedEnum|string|null $type): ?string
    {
        $value = $type instanceof BackedEnum ? (string) $type->value : $type;

        return $value === ElementType::Popup->value ? ElementType::Form->value : $value;
    }

    /**
     * @return array<string, mixed>
     */
    private static function previewConfig(Get $get): array
    {
        $type = $get('type');
        $type = $type instanceof BackedEnum ? $type->value : $type;

        if ($type === ElementType::FloatingButton->value) {
            return [
                'button_text' => $get('config.button_text'),
                'action' => $get('config.action'),
                'icon' => $get('config.icon'),
                'position' => $get('config.position'),
                'size' => $get('config.size'),
                'shape' => $get('config.shape'),
                'color' => $get('config.color'),
                'button_effect' => $get('config.button_effect'),
                'visible_desktop' => $get('config.visible_desktop'),
                'visible_mobile' => $get('config.visible_mobile'),
            ];
        }

        if ($type === ElementType::Popup->value) {
            $rawImage = $get('config.image');
            $imageUrl = null;

            if ($rawImage instanceof TemporaryUploadedFile) {
                try {
                    $imageUrl = $rawImage->temporaryUrl();
                } catch (\Exception) {
                }
            } elseif (is_string($rawImage) && filled($rawImage)) {
                if (Storage::disk('public')->exists($rawImage)) {
                    $imageUrl = Storage::disk('public')->url($rawImage);
                }
            }

            return [
                'title' => $get('config.title'),
                'message' => $get('config.message'),
                'button_text' => $get('config.button_text'),
                'action' => $get('config.action'),
                'trigger' => $get('config.trigger'),
                'delay' => $get('config.delay'),
                'frequency' => $get('config.frequency'),
                'visibility' => $get('config.visibility'),
                'layout' => $get('config.layout'),
                'image' => $rawImage,
                'image_url' => $imageUrl,
                'color' => $get('config.color'),
                'button_effect' => $get('config.button_effect'),
            ];
        }

        if ($type === ElementType::Form->value) {
            $config = [];
            foreach ([
                'button_text', 'button_color', 'button_size', 'corner_radius',
                'show_amount_input', 'template', 'title', 'text_color',
                'background_color', 'icon_color', 'border_size', 'border_radius',
                'border_color', 'show_shadow', 'suggested_amounts', 'default_amount',
                'default_frequency', 'allow_monthly', 'show_dedication',
                'heading', 'description', 'submit_text', 'show_name', 'show_email',
                'show_phone', 'show_message', 'suggested_amounts_one_time',
                'suggested_amounts_monthly', 'show_suggested', 'display_as_popup', 'allow_cover_fee',
                'position', 'vertical_offset', 'horizontal_offset', 'popup_trigger',
                'popup_delay', 'popup_scroll_percentage', 'popup_frequency',
                'popup_allow_close', 'popup_close_on_backdrop', 'popup_max_width',
            ] as $key) {
                $config[$key] = $get('config.'.$key);
            }

            return $config;
        }

        if ($type === ElementType::QrCode->value) {
            $rawSize = $get('config.size');
            $size = is_numeric($rawSize) ? (int) $rawSize : match (strtolower((string) $rawSize)) {
                'small' => 150,
                'medium' => 200,
                'large' => 250,
                'extra large' => 300,
                default => 200,
            };

            return [
                'size' => $size,
                'label' => $get('config.label'),
                'alignment' => $get('config.alignment'),
                'margin_top' => $get('config.margin_top'),
                'margin_bottom' => $get('config.margin_bottom'),
            ];
        }

        if ($type === ElementType::Link->value) {
            return [
                'text' => $get('config.text'),
                'url' => $get('config.url'),
                'action' => $get('config.action'),
                'style' => $get('config.style'),
                'color' => $get('config.color'),
                'button_effect' => $get('config.button_effect'),
                'size' => $get('config.size'),
                'alignment' => $get('config.alignment'),
            ];
        }

        return [
            'button_text' => $get('config.button_text'),
            'button_color' => $get('config.button_color'),
            'button_size' => $get('config.button_size'),
            'corner_radius' => $get('config.corner_radius'),
            'button_effect' => $get('config.button_effect'),
        ];
    }
}
