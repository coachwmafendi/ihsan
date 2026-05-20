<?php

namespace App\Filament\App\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use App\Enums\PaymentGateway;
use App\Filament\Forms\Components\SuggestedAmounts;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Campaign Tabs')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Overview')
                            ->schema([
                                Section::make('General')
                                    ->description('Name, status and donor-facing campaign copy.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Nama kempen')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug()))
                                            ->columnSpanFull(),
                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->columnSpanFull(),
                                        Select::make('status')
                                            ->required()
                                            ->options(CampaignStatus::class),
                                        TextInput::make('headline')
                                            ->label('Tajuk utama')
                                            ->maxLength(255),
                                        TextInput::make('short_summary')
                                            ->label('Ringkasan pendek')
                                            ->maxLength(500)
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Story and media')
                                    ->description('Campaign page content and main image.')
                                    ->schema([
                                        Textarea::make('description')
                                            ->rows(6)
                                            ->columnSpanFull(),
                                        FileUpload::make('image_path')
                                            ->label('Gambar utama')
                                            ->image()
                                            ->directory('campaigns')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Settings')
                            ->schema([
                                Section::make('Frequencies')
                                    ->description('Control donation frequencies and custom amount behavior.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('allow_recurring')
                                            ->label('Allow monthly donations'),
                                        Toggle::make('allow_custom_amount')
                                            ->label('Allow custom amount'),
                                    ]),
                                Section::make('Amounts')
                                    ->description('Set campaign targets, minimum donation rules and timing.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('has_target')
                                            ->label('Tetapkan sasaran kutipan'),
                                        TextInput::make('target_amount')
                                            ->label('Target amount')
                                            ->numeric()
                                            ->prefix('MYR')
                                            ->hidden(fn ($get) => ! $get('has_target')),
                                        TextInput::make('minimum_amount')
                                            ->label('Minimum amount')
                                            ->numeric()
                                            ->prefix('MYR'),
                                        DatePicker::make('end_date')
                                            ->label('Tarikh tamat')
                                            ->format('Y-m-d')
                                            ->displayFormat('d/m/Y'),
                                    ]),
                                Section::make('Suggested Amounts')
                                    ->description('Preset donation buttons shown in the checkout form.')
                                    ->schema([
                                        SuggestedAmounts::make('suggested_amounts')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Payment')
                                    ->description('Choose how this campaign processes donations.')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('payment_gateway')
                                            ->label('Payment gateway')
                                            ->options(PaymentGateway::class)
                                            ->default(PaymentGateway::Stripe),
                                    ]),
                                Section::make('Supporter experience')
                                    ->description('Configure post-donation messaging and redirects.')
                                    ->columns(2)
                                    ->schema([
                                        Textarea::make('thank_you_message')
                                            ->label('Thank you message')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        TextInput::make('redirect_url')
                                            ->label('Redirect URL')
                                            ->url()
                                            ->placeholder('https://')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Checkout Modal')
                            ->schema([
                                Section::make('Embed trigger')
                                    ->description('Settings for script-based modal checkout on client websites.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('checkout_modal_enabled')
                                            ->label('Enable modal checkout')
                                            ->helperText('Allow this campaign to open inside the Ihsan embed modal.')
                                            ->default(false)
                                            ->columnSpanFull(),
                                        TextInput::make('form_parameter')
                                            ->label('Form parameter')
                                            ->helperText('Example: MTMTDEVEFUND. Use ?form=THIS in URL to trigger modal checkout.')
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->columnSpanFull(),
                                        TagsInput::make('checkout_allowed_domains')
                                            ->label('Allowed domains')
                                            ->helperText('Only these client website domains can open this campaign in the embed modal.')
                                            ->placeholder('Add domain')
                                            ->columnSpanFull(),
                                        Placeholder::make('embed_modal_note')
                                            ->label('Install script')
                                            ->content(fn ($get) => new HtmlString(self::embedSnippetHtml($get('form_parameter'))))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Campaign Page')
                            ->schema([
                                Section::make('Hosted page')
                                    ->description('Direct campaign page and hosted donation settings.')
                                    ->schema([
                                        Placeholder::make('hosted_page_note')
                                            ->label('Hosted donation page')
                                            ->content(fn ($record) => new HtmlString(
                                                $record
                                                    ? 'Direct page: <code>'.e(url('/donate/'.$record->slug)).'</code>'
                                                    : 'Save the campaign to generate its hosted donation page URL.'
                                            ))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Stats')
                            ->visible(fn ($record) => $record !== null)
                            ->columns(3)
                            ->schema([
                                Placeholder::make('collected_amount')
                                    ->label('Total Collected')
                                    ->content(fn ($record) => new HtmlString(
                                        '<span class="text-2xl font-bold text-emerald-600">RM '.number_format($record->collected_amount ?? 0, 2).'</span>'
                                    )),
                                Placeholder::make('donation_count')
                                    ->label('Total Donations')
                                    ->content(fn ($record) => new HtmlString(
                                        '<span class="text-2xl font-bold text-zinc-900">'.$record->donations()->count().'</span>'
                                    )),
                                Placeholder::make('campaign_url')
                                    ->label('Campaign URL')
                                    ->content(fn ($record) => new HtmlString(
                                        '<div x-data="{ copied: false, url: \''.e(url('/donate/'.$record->slug)).'\' }" class="flex items-center gap-2">'
                                        .'<code class="flex-1 truncate text-sm text-zinc-600" x-text="url"></code>'
                                        .'<button type="button" x-on:click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" class="shrink-0 rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:bg-zinc-200" x-text="copied ? \'Copied!\' : \'Copy\'"></button>'
                                        .'</div>'
                                    ))
                                    ->columnSpan(2),
                            ]),
                    ]),
            ]);
    }

    private static function embedSnippetHtml(?string $formParameter): string
    {
        $formParameter = filled($formParameter) ? $formParameter : 'FORM_PARAMETER';

        $script = '<script src="'.url('/embed.js').'" async></script>';
        $button = '<button data-ihsan-form="'.$formParameter.'">Donate</button>';
        $link = '<a href="?form='.$formParameter.'">Donate</a>';

        return '<div class="space-y-3">'
            .'<p class="text-sm text-zinc-600">Add the script once on the client website, then use a button or link trigger to open this campaign as a modal checkout.</p>'
            .self::copyableSnippet('Script tag', $script)
            .self::copyableSnippet('Button trigger', $button)
            .self::copyableSnippet('Link trigger', $link)
            .'<p class="text-xs text-zinc-500">Domain allowlist will be enforced by Ihsan before the checkout form opens.</p>'
            .'</div>';
    }

    private static function copyableSnippet(string $label, string $code): string
    {
        return '<div x-data="{ copied: false, code: '.Js::from($code).' }" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">'
            .'<div class="mb-2 flex items-center justify-between gap-3">'
            .'<span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">'.e($label).'</span>'
            .'<button type="button" x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 1500) })" class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100" x-text="copied ? \'Copied\' : \'Copy\'"></button>'
            .'</div>'
            .'<code class="block overflow-x-auto whitespace-pre rounded-md bg-white p-2 text-xs text-zinc-700 ring-1 ring-zinc-200">'.e($code).'</code>'
            .'</div>';
    }
}
