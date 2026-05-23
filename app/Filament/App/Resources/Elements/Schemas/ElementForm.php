<?php

namespace App\Filament\App\Resources\Elements\Schemas;

use App\Enums\ElementType;
use App\Filament\App\Resources\Elements\ElementResource;
use App\Models\Element;
use BackedEnum;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Slider;
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
use Illuminate\Support\HtmlString;

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
                'show_amount_input' => true,
            ],
            ElementType::Form->value,
            ElementType::Popup->value => [
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
                'show_comment' => true,
                'submit_text' => 'Donate and Support',
                'display_as_popup' => $value === ElementType::Popup->value,
            ],
            default => [],
        };

        return $config;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Element Details')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'ihsan-builder-header'])
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
                            ->options(ElementType::class)
                            ->placeholder('Select type'),
                        Select::make('campaign_id')
                            ->label('Campaign')
                            ->relationship('campaign', 'title', modifyQueryUsing: fn (Builder $query) => $query
                                ->where('organization_id', auth()->user()->organization_id))
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->placeholder('Optional: link to a campaign'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->inlineLabel(false)
                            ->helperText('Enable or disable this element'),
                        View::make('filament.forms.components.embed-token')
                            ->viewData(fn (?Element $record): array => [
                                'token' => $record?->token,
                                'url' => $record?->token ? url('/donate/'.$record->token) : null,
                            ])
                            ->visible(fn ($record) => $record !== null)
                            ->columnSpanFull(),
                    ]),
                Section::make('Button Configuration')
                    ->columnSpanFull()
                    ->statePath('config')
                    ->visible(fn (Get $get): bool => self::selectedType($get('type')) === ElementType::Button->value)
                    ->columns(2)
                    ->schema([
                        TextInput::make('button_text')
                            ->label('Button Text')
                            ->default('Donate Now')
                            ->required()
                            ->live(),
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
                            ->live(),
                        Select::make('button_size')
                            ->label('Button Size')
                            ->options([
                                'text-sm px-4 py-2' => 'Small',
                                'text-base px-6 py-3' => 'Medium',
                                'text-lg px-8 py-4' => 'Large',
                            ])
                            ->default('text-base px-6 py-3')
                            ->live(),
                        TextInput::make('corner_radius')
                            ->label('Corner Radius')
                            ->numeric()
                            ->default(8)
                            ->suffix('px')
                            ->live(),
                        Toggle::make('show_amount_input')
                            ->label('Show amount input')
                            ->default(true)
                            ->live(),
                        View::make('filament.forms.components.element-form-submit')
                            ->viewData(fn (?Element $record): array => [
                                'label' => $record ? 'Save changes' : 'Create element',
                                'cancelUrl' => ElementResource::getUrl('index'),
                            ])
                            ->columnSpanFull(),
                    ]),
                Section::make('Popup Configuration')
                    ->columnSpanFull()
                    ->statePath('config')
                    ->visible(fn (Get $get): bool => self::selectedType($get('type')) === ElementType::Popup->value)
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
                        Placeholder::make('amounts_note')
                            ->hiddenLabel()
                            ->content(new HtmlString('<p class="text-sm text-zinc-500">Jumlah derma dicadangkan dan lalai dikawal dari <strong>tetapan kempen</strong>.</p>'))
                            ->columnSpanFull(),
                        Toggle::make('allow_monthly')
                            ->label('Allow monthly donations')
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
                        Toggle::make('show_dedication')
                            ->label('Show dedication option')
                            ->default(true)
                            ->live(),
                        Toggle::make('show_comment')
                            ->label('Show comment field')
                            ->default(true)
                            ->live(),
                        Section::make('Design')
                            ->columnSpanFull()
                            ->columns(2)
                            ->schema([
                                ColorPicker::make('text_color')
                                    ->label('Text color')
                                    ->default('#212830')
                                    ->live(),
                                ColorPicker::make('background_color')
                                    ->label('Background color')
                                    ->default('#FFFFFF')
                                    ->live(),
                                ColorPicker::make('border_color')
                                    ->label('Border color')
                                    ->default('#DEDFF3')
                                    ->live(),
                                Slider::make('border_size')
                                    ->label('Border size')
                                    ->range(0, 8)
                                    ->step(1)
                                    ->default(2)
                                    ->live(),
                                Slider::make('border_radius')
                                    ->label('Border radius')
                                    ->range(0, 24)
                                    ->step(1)
                                    ->default(6)
                                    ->live(),
                                Toggle::make('show_shadow')
                                    ->label('Show shadow')
                                    ->default(false)
                                    ->live(),
                            ]),
                        View::make('filament.forms.components.element-form-submit')
                            ->viewData(fn (?Element $record): array => [
                                'label' => $record ? 'Save changes' : 'Create element',
                                'cancelUrl' => ElementResource::getUrl('index'),
                            ])
                            ->columnSpanFull(),
                    ]),
                Grid::make([
                    'default' => 1,
                    'xl' => 12,
                ])
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'ihsan-builder-shell'])
                    ->visible(fn (Get $get): bool => self::selectedType($get('type')) === ElementType::Form->value)
                    ->schema([
                        Tabs::make('Donation Form Workbench')
                            ->statePath('config')
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 7,
                            ])
                            ->extraAttributes(['class' => 'ihsan-builder-editor'])
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
                                        Placeholder::make('amounts_note')
                                            ->hiddenLabel()
                                            ->content(new HtmlString('<p class="text-sm text-zinc-500">Jumlah derma dicadangkan dan lalai dikawal dari <strong>tetapan kempen</strong>.</p>'))
                                            ->columnSpanFull(),
                                        Toggle::make('allow_monthly')
                                            ->label('Allow monthly donations')
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
                                        Toggle::make('show_dedication')
                                            ->label('Show dedication option')
                                            ->default(true)
                                            ->live(),
                                        Toggle::make('show_comment')
                                            ->label('Show comment field')
                                            ->default(true)
                                            ->live(),
                                        View::make('filament.forms.components.element-form-submit')
                                            ->viewData(fn (?Element $record): array => [
                                                'label' => $record ? 'Save changes' : 'Create element',
                                                'cancelUrl' => ElementResource::getUrl('index'),
                                            ])
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('Design')
                                    ->columns(2)
                                    ->schema([
                                        ColorPicker::make('text_color')
                                            ->label('Text color')
                                            ->default('#212830')
                                            ->live(),
                                        ColorPicker::make('background_color')
                                            ->label('Background color')
                                            ->default('#FFFFFF')
                                            ->live(),
                                        ColorPicker::make('icon_color')
                                            ->label('Icon color')
                                            ->default('#FF435A')
                                            ->live(),
                                        ColorPicker::make('border_color')
                                            ->label('Border color')
                                            ->default('#DEDFF3')
                                            ->live(),
                                        Slider::make('border_size')
                                            ->label('Border size')
                                            ->range(0, 8)
                                            ->step(1)
                                            ->default(2)
                                            ->live(),
                                        Slider::make('border_radius')
                                            ->label('Border radius')
                                            ->range(0, 24)
                                            ->step(1)
                                            ->default(6)
                                            ->live(),
                                        Toggle::make('show_shadow')
                                            ->label('Show shadow')
                                            ->default(false)
                                            ->live(),
                                    ]),
                                Tab::make('Behavior')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('display_as_popup')
                                            ->label('Display as popup')
                                            ->helperText('Show the donation form as a centered modal overlay instead of a full page.')
                                            ->default(false)
                                            ->live()
                                            ->hidden(fn (Get $get): bool => $get('type') === 'popup'),
                                        Select::make('success_action')
                                            ->label('After Donation')
                                            ->options([
                                                'message' => 'Show Success Message',
                                                'redirect' => 'Redirect to URL',
                                            ])
                                            ->default('message')
                                            ->live(),
                                        TextInput::make('success_message')
                                            ->label('Success Message')
                                            ->default('Thank you for your donation!')
                                            ->visible(fn (Get $get): bool => $get('success_action') === 'message')
                                            ->live(),
                                        TextInput::make('redirect_url')
                                            ->label('Redirect URL')
                                            ->url()
                                            ->visible(fn (Get $get): bool => $get('success_action') === 'redirect')
                                            ->live(),
                                        TextInput::make('thank_you_title')
                                            ->label('Thank You Title')
                                            ->default('Thank You!')
                                            ->live(),
                                        Textarea::make('thank_you_description')
                                            ->label('Thank You Description')
                                            ->rows(2)
                                            ->live()
                                            ->columnSpanFull(),
                                    ]),
                                Tab::make('Embed')
                                    ->schema([
                                        Placeholder::make('embed_content')
                                            ->hiddenLabel()
                                            ->content(fn (?Element $record): HtmlString => new HtmlString(
                                                $record?->token
                                                    ? static::embedTabHtml($record)
                                                    : '<p class="text-sm text-zinc-500">Simpan element untuk mendapatkan kod benam.</p>'
                                            ))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Section::make('Live Preview')
                            ->columnSpan([
                                'default' => 1,
                                'xl' => 5,
                            ])
                            ->extraAttributes(['class' => 'ihsan-builder-preview xl:sticky xl:top-6'])
                            ->schema([
                                View::make('filament.forms.components.element-preview')
                                    ->viewData(fn (Get $get): array => [
                                        'type' => $get('type'),
                                        'name' => $get('name'),
                                        'config' => self::previewConfig($get),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private static function selectedType(BackedEnum|string|null $type): ?string
    {
        $value = $type instanceof BackedEnum ? (string) $type->value : $type;

        return $value === ElementType::Popup->value ? ElementType::Form->value : $value;
    }

    private static function embedTabHtml(Element $record): string
    {
        $baseUrl = url('/donate/'.$record->token);
        $embedUrl = $baseUrl.'?embed=1';
        $urlEncoded = urlencode($baseUrl);
        $waUrl = 'https://wa.me/?text='.$urlEncoded;
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data='.$urlEncoded.'&bgcolor=ffffff&color=0f172a&qzone=1';
        $waIcon = '<svg class="size-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>';

        $iframe = implode("\n", [
            '<iframe',
            '  src="'.e($embedUrl).'"',
            '  width="100%"',
            '  height="700"',
            '  frameborder="0"',
            '  allow="payment *"',
            '  style="border:0;border-radius:16px;"',
            '></iframe>',
        ]);

        return '<div class="space-y-4">'
            .'<p class="text-sm text-zinc-500">Letak kod berikut dalam mana-mana halaman web untuk benamkan borang derma terus tanpa pop-up.</p>'
            .self::copyableSnippet('Kod benam', $iframe)
            .'<div class="flex items-start gap-6 pt-2">'
            .'<div class="shrink-0 text-center">'
            .'<img src="'.e($qrUrl).'" width="140" height="140" loading="lazy" class="rounded-lg border border-zinc-200" alt="QR Code">'
            .'<p class="mt-1.5 text-xs text-zinc-400">Imbas untuk derma</p>'
            .'</div>'
            .'<div class="flex-1 space-y-3 pt-1">'
            .'<p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Kongsi</p>'
            .'<a href="'.e($waUrl).'" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600 transition">'
            .$waIcon.'WhatsApp'
            .'</a>'
            .'</div>'
            .'</div>'
            .'<p class="text-xs text-zinc-400">Laraskan <code class="bg-zinc-100 px-1 rounded">height</code> mengikut keperluan. Nilai <code class="bg-zinc-100 px-1 rounded">700</code> sesuai untuk kebanyakan skrin.</p>'
            .'</div>';
    }

    private static function copyableSnippet(string $label, string $code): string
    {
        return '<div x-data="{ copied: false }" data-code="'.e($code).'" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">'
            .'<div class="mb-2 flex items-center justify-between gap-3">'
            .'<span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">'.e($label).'</span>'
            .'<button type="button" x-on:click="navigator.clipboard.writeText($root.dataset.code).then(() => { copied = true; setTimeout(() => copied = false, 1500) })" class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100" x-text="copied ? \'Disalin\' : \'Salin\'"></button>'
            .'</div>'
            .'<code class="block overflow-x-auto whitespace-pre rounded-md bg-white p-2 text-xs text-zinc-700 ring-1 ring-zinc-200">'.e($code).'</code>'
            .'</div>';
    }

    /**
     * @return array<string, mixed>
     */
    private static function previewConfig(Get $get): array
    {
        return [
            'button_text' => $get('config.button_text'),
            'button_color' => $get('config.button_color'),
            'button_size' => $get('config.button_size'),
            'corner_radius' => $get('config.corner_radius'),
            'show_amount_input' => $get('config.show_amount_input'),
            'template' => $get('config.template'),
            'title' => $get('config.title'),
            'text_color' => $get('config.text_color'),
            'background_color' => $get('config.background_color'),
            'icon_color' => $get('config.icon_color'),
            'border_size' => $get('config.border_size'),
            'border_radius' => $get('config.border_radius'),
            'border_color' => $get('config.border_color'),
            'show_shadow' => $get('config.show_shadow'),
            'suggested_amounts' => $get('config.suggested_amounts'),
            'default_amount' => $get('config.default_amount'),
            'default_frequency' => $get('config.default_frequency'),
            'allow_monthly' => $get('config.allow_monthly'),
            'show_dedication' => $get('config.show_dedication'),
            'show_comment' => $get('config.show_comment'),
            'heading' => $get('config.heading'),
            'description' => $get('config.description'),
            'submit_text' => $get('config.submit_text'),
            'show_name' => $get('config.show_name'),
            'show_email' => $get('config.show_email'),
            'show_phone' => $get('config.show_phone'),
            'show_message' => $get('config.show_message'),
            'suggested_amounts_one_time' => $get('config.suggested_amounts_one_time'),
            'suggested_amounts_monthly' => $get('config.suggested_amounts_monthly'),
            'show_suggested' => $get('config.show_suggested'),
            'display_as_popup' => $get('config.display_as_popup'),
        ];
    }
}
