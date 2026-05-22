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
use Filament\Forms\Components\TagsInput;
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
                'suggested_amounts_one_time' => [500, 200, 100, 50, 40, 30],
                'suggested_amounts_monthly' => [100, 50, 30, 20, 10, 5],
                'default_amount' => 5,
                'default_frequency' => 'monthly',
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
                        TagsInput::make('suggested_amounts_one_time')
                            ->label('One-time amounts')
                            ->default([500, 200, 100, 50, 40, 30])
                            ->placeholder('Add amount')
                            ->live()
                            ->columnSpanFull(),
                        TagsInput::make('suggested_amounts_monthly')
                            ->label('Monthly amounts')
                            ->default([100, 50, 30, 20, 10, 5])
                            ->placeholder('Add amount')
                            ->live()
                            ->columnSpanFull(),
                        TextInput::make('default_amount')
                            ->label('Default amount')
                            ->numeric()
                            ->default(5)
                            ->minValue(1)
                            ->live(),
                        Select::make('default_frequency')
                            ->label('Default frequency')
                            ->options([
                                'one_time' => 'One-time',
                                'monthly' => 'Monthly',
                            ])
                            ->default('monthly')
                            ->live(),
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
                                        Section::make('Suggested Amounts')
                                            ->description('Configure donation amounts for each frequency type.')
                                            ->columnSpanFull()
                                            ->extraAttributes(['class' => 'border-t border-zinc-200 pt-4'])
                                            ->schema([
                                                TagsInput::make('suggested_amounts_one_time')
                                                    ->label('One-time amounts')
                                                    ->helperText('Amounts shown when donor selects one-time donation.')
                                                    ->default([500, 200, 100, 50, 40, 30])
                                                    ->placeholder('Add amount')
                                                    ->live()
                                                    ->columnSpanFull(),
                                                TagsInput::make('suggested_amounts_monthly')
                                                    ->label('Monthly amounts')
                                                    ->helperText('Amounts shown when donor selects monthly donation.')
                                                    ->default([100, 50, 30, 20, 10, 5])
                                                    ->placeholder('Add amount')
                                                    ->live()
                                                    ->columnSpanFull(),
                                            ]),
                                        TextInput::make('default_amount')
                                            ->label('Default amount')
                                            ->numeric()
                                            ->default(5)
                                            ->minValue(1)
                                            ->live(),
                                        Select::make('default_frequency')
                                            ->label('Default frequency')
                                            ->options([
                                                'one_time' => 'One-time',
                                                'monthly' => 'Monthly',
                                            ])
                                            ->default('monthly')
                                            ->live(),
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
                                        Placeholder::make('iframe_code')
                                            ->label('Iframe Embed Code')
                                            ->helperText('Embed this code into your website to display the donation form inline.')
                                            ->extraAttributes(['class' => '[&_.fi-fo-placeholder-label]:font-normal [&_.fi-fo-placeholder-content]:items-start'])
                                            ->content(fn (?Element $record): HtmlString => new HtmlString(
                                                '<div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4">'
                                                .'<div x-data="{ copied: false, copyText: \''.str_replace("'", "\\'", self::iframeCode($record)).'\', doCopy() { let t = document.createElement(\'textarea\'); t.value = this.copyText; document.body.appendChild(t); t.select(); document.execCommand(\'copy\'); document.body.removeChild(t); this.copied = true; setTimeout(() => this.copied = false, 2000) } }" class="flex items-start gap-3">'
                                                .'<code class="flex-1 whitespace-pre-wrap break-all font-mono text-xs leading-relaxed text-zinc-700">'.e(self::iframeCode($record)).'</code>'
                                                .'<div class="relative shrink-0">'
                                                .'<button type="button" x-on:click="doCopy()" class="flex items-center justify-center rounded-lg bg-white px-3 py-2 text-xs font-medium text-zinc-600 shadow-sm ring-1 ring-zinc-200 transition hover:bg-zinc-50 hover:text-zinc-800" title="Copy code">'
                                                .'<svg class="mr-1.5 size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>'
                                                .'Copy'
                                                .'</button>'
                                                .'<span x-show="copied" x-cloak class="absolute -top-9 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-md bg-emerald-600 px-2.5 py-1 text-xs font-medium text-white shadow-sm">Copied!</span>'
                                                .'</div>'
                                                .'</div>'
                                                .'</div>'
                                            )),
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

    private static function hostedUrl(?Element $record): string
    {
        if (! $record?->token) {
            return 'Available after saving this element.';
        }

        return url('/donate/'.$record->token);
    }

    private static function iframeCode(?Element $record): string
    {
        if (! $record?->token) {
            return 'Available after saving this element.';
        }

        $url = self::hostedUrl($record);

        if ($record->type === ElementType::Popup || $record->config('display_as_popup')) {
            $url .= '?popup=1';
        }

        return '<iframe src="'.$url.'" width="100%" height="760" style="border:0;" loading="lazy"></iframe>';
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
