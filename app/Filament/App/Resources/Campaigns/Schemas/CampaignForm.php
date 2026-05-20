<?php

namespace App\Filament\App\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class CampaignForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Campaign Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
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
                        DatePicker::make('end_date'),
                    ]),
                Section::make('Description & Media')
                    ->schema([
                        Textarea::make('description')
                            ->rows(5)
                            ->columnSpanFull(),
                        FileUpload::make('image_path')
                            ->image()
                            ->directory('campaigns')
                            ->columnSpanFull(),
                    ]),
                Section::make('Fundraising')
                    ->columns(2)
                    ->schema([
                        Toggle::make('has_target')
                            ->label('Set a fundraising target'),
                        TextInput::make('target_amount')
                            ->numeric()
                            ->prefix('MYR')
                            ->hidden(fn ($get) => ! $get('has_target')),
                        Toggle::make('allow_recurring')
                            ->label('Allow recurring donations'),
                        Repeater::make('suggested_amounts')
                            ->label('Suggested donation amounts')
                            ->schema([
                                TextInput::make('amount')
                                    ->numeric()
                                    ->prefix('MYR')
                                    ->required(),
                                TextInput::make('label')
                                    ->maxLength(100),
                            ])
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ]),
                Section::make('Campaign Stats')
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
                                '<div class="flex items-center gap-2">'
                                .'<code class="flex-1 truncate text-sm text-zinc-600">'.e(url('/donate/'.$record->slug)).'</code>'
                                .'<button type="button" onclick="navigator.clipboard.writeText(\''.e(url('/donate/'.$record->slug)).'\'); this.textContent=\'Copied!\'; setTimeout(() => this.textContent=\'Copy\', 2000)" class="shrink-0 rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:bg-zinc-200">Copy</button>'
                                .'</div>'
                            ))
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
