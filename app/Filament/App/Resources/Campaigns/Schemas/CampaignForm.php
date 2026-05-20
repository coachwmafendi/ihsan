<?php

namespace App\Filament\App\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use App\Enums\PaymentGateway;
use App\Filament\Forms\Components\SuggestedAmounts;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Campaign Tabs')
                    ->vertical()
                    ->tabs([
                        Tab::make('Details')
                            ->columns(2)
                            ->schema([
                                TextInput::make('title')
                                    ->label('Nama kempen')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, $set) => $set('slug', str($state)->slug())),
                                TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                                Select::make('status')
                                    ->required()
                                    ->options(CampaignStatus::class),
                                TextInput::make('headline')
                                    ->label('Tajuk utama')
                                    ->maxLength(255),
                                TextInput::make('short_summary')
                                    ->label('Ringkasan pendek')
                                    ->maxLength(500),
                            ]),
                        Tab::make('Media')
                            ->schema([
                                Textarea::make('description')
                                    ->rows(5)
                                    ->columnSpanFull(),
                                FileUpload::make('image_path')
                                    ->label('Gambar utama')
                                    ->image()
                                    ->directory('campaigns')
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Fundraising')
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
                                Toggle::make('allow_custom_amount')
                                    ->label('Allow custom amount'),
                                Toggle::make('allow_recurring')
                                    ->label('Allow recurring donations'),
                                DatePicker::make('end_date')
                                    ->label('Tarikh tamat'),
                            ]),
                        Tab::make('Suggested')
                            ->schema([
                                SuggestedAmounts::make('suggested_amounts')
                                    ->columnSpanFull(),
                            ]),
                        Tab::make('Settings')
                            ->schema([
                                Select::make('payment_gateway')
                                    ->label('Payment gateway')
                                    ->options(PaymentGateway::class)
                                    ->default(PaymentGateway::Stripe),
                                Textarea::make('thank_you_message')
                                    ->label('Thank you message')
                                    ->rows(3),
                                TextInput::make('redirect_url')
                                    ->label('Redirect URL')
                                    ->url()
                                    ->placeholder('https://'),
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
}
