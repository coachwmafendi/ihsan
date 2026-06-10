<?php

namespace App\Filament\App\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Filament\Forms\Components\SuggestedAmounts;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

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
                            ->icon('heroicon-o-chart-bar')
                            ->visible(fn ($record) => $record !== null)
                            ->schema([
                                Section::make('Campaign Info')
                                    ->columns(3)
                                    ->schema([
                                        Placeholder::make('_info_title')
                                            ->label('Campaign name')
                                            ->content(fn ($record) => new HtmlString('<span class="text-base font-semibold text-zinc-900 dark:text-zinc-100">'.e($record->title).'</span>')),
                                        Placeholder::make('_info_status')
                                            ->label('Status')
                                            ->content(function ($record): HtmlString {
                                                $color = match ((string) $record->status->name) {
                                                    'Active' => 'bg-emerald-600 text-white',
                                                    'Draft' => 'bg-gray-500 text-white',
                                                    'Paused' => 'bg-amber-500 text-white',
                                                    'Ended' => 'bg-red-600 text-white',
                                                    default => 'bg-gray-500 text-white',
                                                };

                                                return new HtmlString('<span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold '.$color.'">'.$record->status->name.'</span>');
                                            }),
                                        Placeholder::make('_info_dates')
                                            ->label('Duration')
                                            ->content(function ($record): HtmlString {
                                                $parts = [];
                                                if ($record->has_target && $record->target_amount) {
                                                    $parts[] = 'Target: MYR '.number_format((float) $record->target_amount, 2);
                                                }
                                                if ($record->has_end_date && $record->end_date) {
                                                    $parts[] = 'Ends: '.$record->end_date->format('d M Y');
                                                }
                                                if (empty($parts)) {
                                                    $parts[] = 'No target or end date set';
                                                }

                                                return new HtmlString('<span class="text-sm text-zinc-600 dark:text-zinc-400">'.implode(' · ', $parts).'</span>');
                                            }),
                                    ]),
                                Grid::make()
                                    ->columns(4)
                                    ->schema([
                                        Placeholder::make('collected_amount')
                                            ->label('Amount raised')
                                            ->content(function ($record): HtmlString {
                                                $hasNonMyr = $record->donations()
                                                    ->where('status', DonationStatus::Succeeded)
                                                    ->where('currency', '!=', 'MYR')
                                                    ->exists();
                                                $prefix = $hasNonMyr ? '≈ RM ' : 'RM ';
                                                $amount = $record->donations()
                                                    ->where('status', DonationStatus::Succeeded)
                                                    ->sum('base_amount') ?? 0;

                                                return new HtmlString('<span class="text-2xl font-bold text-emerald-600">'.$prefix.number_format($amount, 2).'</span>');
                                            }),
                                        Placeholder::make('donation_count')
                                            ->label('Donations')
                                            ->content(fn ($record) => new HtmlString(
                                                '<span class="text-2xl font-bold text-zinc-900">'.$record->donations()->count().'</span>'
                                            )),
                                        Placeholder::make('active_recurring')
                                            ->label('Active recurring plans')
                                            ->content(fn ($record) => new HtmlString(
                                                '<span class="text-2xl font-bold text-teal-600">'.number_format(
                                                    $record->subscriptions()->where('status', 'active')->count()
                                                ).'</span>'
                                            )),
                                        Placeholder::make('last_donation')
                                            ->label('Last donation')
                                            ->content(fn ($record) => new HtmlString(
                                                '<span class="text-lg font-semibold text-zinc-900">'.(
                                                    $record->donations()
                                                        ->where('status', DonationStatus::Succeeded)
                                                        ->latest('created_at')
                                                        ->first()
                                                        ?->created_at
                                                        ?->format('d M Y')
                                                    ?? '—'
                                                ).'</span>'
                                            )),
                                    ]),
                                Placeholder::make('progress_bar')
                                    ->label('Campaign performance')
                                    ->columnSpanFull()
                                    ->content(fn ($record) => new HtmlString(
                                        self::progressBarHtml($record)
                                    )),
                                Section::make('Checkout Configuration')
                                    ->columns(3)
                                    ->schema([
                                        Placeholder::make('_cfg_min')
                                            ->label('Minimum amount')
                                            ->content(fn ($record) => new HtmlString('<span class="text-base font-semibold text-zinc-900 dark:text-zinc-100">MYR '.number_format((float) $record->minimum_amount, 2).'</span>')),
                                        Placeholder::make('_cfg_frequency')
                                            ->label('Default frequency')
                                            ->content(function ($record): HtmlString {
                                                $freq = $record->config['default_frequency'] ?? 'monthly';

                                                return new HtmlString('<span class="text-base font-semibold text-zinc-900 dark:text-zinc-100">'.ucfirst($freq).'</span>');
                                            }),
                                        Placeholder::make('_cfg_default_amount')
                                            ->label('Default amount')
                                            ->content(function ($record): HtmlString {
                                                $amount = $record->config['default_amount'] ?? 30;

                                                return new HtmlString('<span class="text-base font-semibold text-zinc-900 dark:text-zinc-100">MYR '.number_format((float) $amount, 2).'</span>');
                                            }),
                                        Placeholder::make('_cfg_recurring')
                                            ->label('Recurring donations')
                                            ->content(fn ($record) => new HtmlString(
                                                $record->allow_recurring
                                                    ? '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">Enabled</span>'
                                                    : '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 ring-1 ring-gray-600/10">Disabled</span>'
                                            )),
                                        Placeholder::make('_cfg_custom')
                                            ->label('Custom amount')
                                            ->content(fn ($record) => new HtmlString(
                                                $record->allow_custom_amount
                                                    ? '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">Enabled</span>'
                                                    : '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 ring-1 ring-gray-600/10">Disabled</span>'
                                            )),
                                        Placeholder::make('_cfg_cover_fee')
                                            ->label('Cover fee')
                                            ->content(function ($record): HtmlString {
                                                $on = $record->config['allow_cover_fee'] ?? true;

                                                return new HtmlString(
                                                    $on
                                                        ? '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">Enabled</span>'
                                                        : '<span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-700 ring-1 ring-gray-600/10">Disabled</span>'
                                                );
                                            }),
                                    ]),
                                Section::make('Currencies')
                                    ->columns(1)
                                    ->schema([
                                        Placeholder::make('_cfg_currencies')
                                            ->label('Accepted currencies')
                                            ->content(function ($record): HtmlString {
                                                $currencies = $record?->organization?->settings['accepted_currencies']
                                                    ?? auth()->user()?->organization?->settings['accepted_currencies']
                                                    ?? ['myr'];
                                                $labels = [];
                                                foreach ($currencies as $c) {
                                                    $labels[] = strtoupper($c);
                                                }

                                                return new HtmlString(
                                                    '<div class="flex flex-wrap gap-2">'.implode('', array_map(fn ($l) => '<span class="inline-flex items-center rounded-md bg-zinc-100 px-2.5 py-1 text-xs font-semibold text-zinc-800 ring-1 ring-zinc-300/50">'.$l.'</span>', $labels)).'</div>'
                                                );
                                            }),
                                    ]),
                            ]),
                        Tab::make('Settings')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('General')
                                    ->description('Campaign name, status, and copy for donors.')
                                    ->columns(2)
                                    ->schema([
                                        TextInput::make('title')
                                            ->label('Campaign name')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                        Select::make('status')
                                            ->label('Status')
                                            ->required()
                                            ->options(CampaignStatus::class),
                                    ]),
                                Section::make('Story & Media')
                                    ->description('Campaign page content and hero image.')
                                    ->schema([
                                        FileUpload::make('image_path')
                                            ->label('Hero image')
                                            ->image()
                                            ->directory('campaigns')
                                            ->helperText('Recommended size: 1920 × 1060 px')
                                            ->columnSpanFull(),
                                        RichEditor::make('description')
                                            ->label('Description')
                                            ->columnSpanFull()
                                            ->extraInputAttributes(['style' => 'min-height: 300px;'])
                                            ->live(debounce: 1500)
                                            ->hint(fn ($state): HtmlString => new HtmlString(static::wordCountHint($state))),
                                    ]),
                                Section::make('Goal & Duration')
                                    ->description('Set campaign target and duration.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('has_target')
                                            ->label('Set fundraising target')
                                            ->live()
                                            ->columnSpanFull(),
                                        TextInput::make('target_amount')
                                            ->label('Target amount')
                                            ->numeric()
                                            ->prefix('MYR')
                                            ->disabled(fn ($get) => ! $get('has_target'))
                                            ->columnSpan(1),
                                        Toggle::make('has_end_date')
                                            ->label('Select a date for this campaign to end')
                                            ->live()
                                            ->columnSpanFull(),
                                        DatePicker::make('end_date')
                                            ->label('Campaign end date')
                                            ->helperText('On that date, the campaign will be disabled and moved to your archive.')
                                            ->format('Y-m-d')
                                            ->displayFormat('d/m/Y')
                                            ->disabled(fn ($get) => ! $get('has_end_date'))
                                            ->columnSpan(1),
                                    ]),
                            ]),
                        Tab::make('Checkout Modal Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        Section::make('Donation Settings')
                                            ->description('Control donation frequency, custom amounts, and minimum amount.')
                                            ->columnSpan(1)
                                            ->columns(2)
                                            ->schema([
                                                Toggle::make('allow_recurring')
                                                    ->label('Enable monthly donations'),
                                                Toggle::make('allow_custom_amount')
                                                    ->label('Allow custom amount'),
                                                TextInput::make('minimum_amount')
                                                    ->label('Minimum amount')
                                                    ->helperText('Minimum amount for each donation.')
                                                    ->numeric()
                                                    ->minValue(0)
                                                    ->step(1)
                                                    ->default(5),
                                            ]),
                                    ]),
                                Section::make('Suggested Amounts')
                                    ->description('Preset amount buttons on the checkout page.')
                                    ->schema([
                                        SuggestedAmounts::make('suggested_amounts')
                                            ->label('Suggested amounts')
                                            ->acceptedCurrencies(fn ($record): array => (
                                                $record?->organization?->settings['accepted_currencies']
                                                ?? auth()->user()?->organization?->settings['accepted_currencies']
                                                ?? ['myr']
                                            ))
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Donation Form Defaults')
                                    ->description('Default behavior when the donation form opens via campaign link (no element override).')
                                    ->columns(2)
                                    ->statePath('config')
                                    ->schema([
                                        Select::make('default_frequency')
                                            ->label('Default frequency')
                                            ->options([
                                                'monthly' => 'Monthly',
                                                'one_time' => 'One-time',
                                            ])
                                            ->default('monthly')
                                            ->native(false),
                                        TextInput::make('default_amount')
                                            ->label('Default amount')
                                            ->numeric()
                                            ->minValue(1)
                                            ->default(30),
                                        Checkbox::make('allow_cover_fee')
                                            ->label('Allow donors to cover processing fee')
                                            ->helperText('Donors see a pre-checked option to cover the Stripe fee (~3% + RM 0.50).')
                                            ->default(true),
                                        Checkbox::make('show_comment')
                                            ->label('Comment (optional)')
                                            ->helperText('Show the comment/message field on the checkout form.')
                                            ->default(true),
                                    ]),
                            ]),
                        Tab::make('Actions')
                            ->icon('heroicon-o-bolt')
                            ->visible(fn ($record) => $record !== null)
                            ->schema([
                                Grid::make()
                                    ->columns(1)
                                    ->schema([
                                        Placeholder::make('archive_section')
                                            ->label('')
                                            ->content(fn ($record) => new HtmlString(
                                                '<div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">'
                                                .'<div class="flex items-start justify-between gap-4">'
                                                .'<div class="min-w-0">'
                                                .'<h3 class="text-sm font-semibold text-zinc-900">'.e($record->title).'</h3>'
                                                .'<div class="mt-1 flex items-center gap-2" x-data="{ copied: false }">'
                                                .'<span class="text-xs text-zinc-500">ID '.e($record->public_id).'</span>'
                                                .'<button type="button" x-on:click="navigator.clipboard.writeText(\''.e($record->public_id).'\').then(() => { copied = true; setTimeout(() => copied = false, 1500) })" class="inline-flex items-center text-zinc-400 hover:text-zinc-600 transition">'
                                                .'<svg x-show="!copied" class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5" /></svg>'
                                                .'<svg x-show="copied" class="size-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>'
                                                .'</button>'
                                                .'</div>'
                                                .'<p class="mt-2 text-sm text-zinc-500">Archive this campaign to hide it from the public and move it to your archive. Archived campaigns can be restored later.</p>'
                                                .'</div>'
                                                .'<button type="button" wire:click="archiveCampaign" wire:confirm="Are you sure you want to archive this campaign? It will no longer accept donations." class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-amber-50 px-4 py-2 text-sm font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 hover:bg-amber-100 transition">'
                                                .'<svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>'
                                                .'Archive Campaign</button>'
                                                .'</div>'
                                                .'</div>'
                                            )),
                                        Placeholder::make('duplicate_section')
                                            ->label('')
                                            ->content(fn ($record) => new HtmlString(
                                                '<div class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">'
                                                .'<div class="flex items-start justify-between gap-4">'
                                                .'<div class="min-w-0">'
                                                .'<h3 class="text-sm font-semibold text-zinc-900">'.e($record->title).'</h3>'
                                                .'<div class="mt-1 flex items-center gap-2" x-data="{ copied: false }">'
                                                .'<span class="text-xs text-zinc-500">ID '.e($record->public_id).'</span>'
                                                .'<button type="button" x-on:click="navigator.clipboard.writeText(\''.e($record->public_id).'\').then(() => { copied = true; setTimeout(() => copied = false, 1500) })" class="inline-flex items-center text-zinc-400 hover:text-zinc-600 transition">'
                                                .'<svg x-show="!copied" class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5" /></svg>'
                                                .'<svg x-show="copied" class="size-3.5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>'
                                                .'</button>'
                                                .'</div>'
                                                .'<p class="mt-2 text-sm text-zinc-500">Create a copy of this campaign with the same settings. Donations and stats will not be copied.</p>'
                                                .'</div>'
                                                .'<button type="button" wire:click="duplicateCampaign" wire:confirm="Are you sure you want to duplicate this campaign? A new campaign will be created with the same settings." class="shrink-0 inline-flex items-center gap-1.5 rounded-lg bg-zinc-50 px-4 py-2 text-sm font-medium text-zinc-700 ring-1 ring-inset ring-zinc-300 hover:bg-zinc-100 transition">'
                                                .'<svg class="size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5" /></svg>'
                                                .'Duplicate Campaign</button>'
                                                .'</div>'
                                                .'</div>'
                                            )),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    private static function progressBarHtml($record): string
    {
        if (! $record || ! $record->has_target || ! $record->target_amount) {
            return '<p class="text-sm text-gray-500 dark:text-gray-400">No target set for this campaign.</p>';
        }

        $collected = (float) $record->donations()->where('status', DonationStatus::Succeeded)->sum('base_amount');
        $target = (float) $record->target_amount;
        $percentage = min(round(($collected / $target) * 100, 1), 100);
        $remaining = max($target - $collected, 0);
        $statusText = $percentage >= 100 ? 'Goal reached!' : 'Remaining: RM '.number_format($remaining, 2);

        return Blade::render(
            '<x-ui.progress-bar'
            .' percentage="'.(int) $percentage.'"'
            .' label="RM '.number_format($collected, 2).'"'
            .' sublabel="of RM '.number_format($target, 2).' · '.$statusText.'"'
            .' size="lg"'
            .' />'
        );
    }

    private static function campaignPageShareHtml($record): string
    {
        $elements = $record->elements;

        if ($elements->isEmpty()) {
            return '<p class="text-sm text-zinc-500">No element linked. Create an element first.</p>';
        }

        $parts = [];
        foreach ($elements as $element) {
            $parts[] = self::shareableUrlHtml(route('donations.show', $element));
        }

        return implode('<hr class="my-6 border-zinc-100">', $parts);
    }

    private static function shareableUrlHtml(string $url): string
    {
        $urlEncoded = urlencode($url);
        $waUrl = 'https://wa.me/?text='.$urlEncoded;
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data='.$urlEncoded.'&bgcolor=ffffff&color=0f172a&qzone=1';
        $waIcon = '<svg class="size-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>';

        return '<div class="space-y-4">'
            .'<div x-data="{ copied: false }" data-url="'.e($url).'" class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2">'
            .'<code class="flex-1 truncate text-sm text-zinc-700">'.e($url).'</code>'
            .'<button type="button" x-on:click="navigator.clipboard.writeText($root.dataset.url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100 transition" x-text="copied ? \'Copied!\' : \'Copy\'"></button>'
            .'<a href="'.e($url).'" target="_blank" rel="noopener" class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100 transition">Open →</a>'
            .'</div>'
            .'<div class="flex items-start gap-6">'
            .'<div class="shrink-0 text-center">'
            .'<img src="'.e($qrUrl).'" width="140" height="140" loading="lazy" class="rounded-lg border border-zinc-200" alt="QR Code">'
            .'<p class="mt-1.5 text-xs text-zinc-400">Scan to donate</p>'
            .'</div>'
            .'<div class="flex-1 space-y-3 pt-1">'
            .'<p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Share</p>'
            .'<a href="'.e($waUrl).'" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white hover:bg-green-600 transition">'
            .$waIcon.'WhatsApp'
            .'</a>'
            .'</div>'
            .'</div>'
            .'</div>';
    }

    private static function embedIframeHtml(string $url, string $id): string
    {
        $safeId = 'ihsan-'.e($id);

        return implode("\n", [
            '<style>',
            '@media(min-width:768px){#'.$safeId.'{max-width:50%;margin:0 auto}}',
            '#'.$safeId.'{width:100%}',
            '</style>',
            '<div id="'.$safeId.'">',
            '<iframe',
            '  src="'.e($url).'?embed=1"',
            '  width="100%"',
            '  height="700"',
            '  frameborder="0"',
            '  allow="payment *"',
            '  style="border:0;border-radius:16px;"',
            '></iframe>',
            '</div>',
        ]);
    }

    private static function inlineEmbedHtml($record): string
    {
        $elements = $record->elements;

        if ($elements->isEmpty()) {
            return '<p class="text-sm text-zinc-500">No element — create an element first.</p>';
        }

        $parts = ['<p class="text-sm text-zinc-500 mb-4">Paste the following code into any web page to embed the donation form directly without a pop-up.</p>'];

        foreach ($elements as $element) {
            $url = route('donations.show', $element);
            $iframe = self::embedIframeHtml($url, $element->token);
            $parts[] = self::copyableSnippet('Embed code', $iframe);
        }

        $parts[] = '<p class="text-xs text-zinc-400 mt-3">Adjust <code class="bg-zinc-100 px-1 rounded">height</code> as needed. A value of <code class="bg-zinc-100 px-1 rounded">700</code> fits most screens.</p>';

        return implode('', $parts);
    }

    private static function donateUrlsCopyableHtml($record): string
    {
        $elements = $record->elements;

        if ($elements->isEmpty()) {
            return '<p class="text-sm text-zinc-500">No URL — create an element first.</p>';
        }

        $parts = [];
        foreach ($elements as $element) {
            $base = route('donations.show', $element);
            $parts[] = self::labeledUrlRow('Wide', $base.'?popup=1')
                .self::labeledUrlRow('Compact', $base);
        }

        return implode('', $parts);
    }

    private static function labeledUrlRow(string $label, string $url): string
    {
        return '<div class="flex items-center gap-2 py-1" x-data="{ copied: false }" data-url=\''.e($url).'\'>'
            .'<span class="w-14 shrink-0 text-xs font-medium text-zinc-400">'.$label.'</span>'
            .'<code class="flex-1 truncate text-sm text-zinc-600">'.e($url).'</code>'
            .'<button type="button" x-on:click="navigator.clipboard.writeText($root.dataset.url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" class="shrink-0 rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:bg-zinc-200" x-text="copied ? \'Copied!\' : \'Copy\'"></button>'
            .'<a href="'.e($url).'" target="_blank" rel="noopener" class="shrink-0 rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:bg-zinc-200">Open →</a>'
            .'</div>';
    }

    private static function embedSnippetHtml(?string $formParameter): string
    {
        $formParameter = filled($formParameter) ? $formParameter : 'FORM_PARAMETER';

        $script = '<script src="'.url('/embed.js').'" async></script>';
        $btnStyle = 'display:inline-flex;align-items:center;gap:6px;padding:10px 20px;background:#0d9488;color:#fff;font:600 14px/1 system-ui,sans-serif;border:0;border-radius:8px;cursor:pointer;text-decoration:none;box-shadow:0 2px 6px rgba(13,148,136,.3);transition:background .15s,transform .15s;letter-spacing:.01em;white-space:nowrap;width:auto';
        $button = '<button data-ihsan-form="'.$formParameter.'" style="'.$btnStyle.'" onmouseover="this.style.background=\'#0f766e\'" onmouseout="this.style.background=\'#0d9488\'" onmousedown="this.style.transform=\'scale(.97)\'" onmouseup="this.style.transform=\'\'">Donate</button>';
        $link = '<a href="?form='.$formParameter.'" style="'.$btnStyle.'" onmouseover="this.style.background=\'#0f766e\'" onmouseout="this.style.background=\'#0d9488\'" onmousedown="this.style.transform=\'scale(.97)\'" onmouseup="this.style.transform=\'\'">Donate</a>';

        $step = fn (int $n, string $title, string $body) => '<div class="flex gap-4">'
            .'<div class="shrink-0 flex size-7 items-center justify-center rounded-full bg-teal-600 text-white text-xs font-bold">'.$n.'</div>'
            .'<div class="space-y-2 flex-1 pb-5">'
            .'<p class="text-sm font-semibold text-zinc-800">'.$title.'</p>'
            .'<p class="text-sm text-zinc-500">'.$body.'</p>';

        return '<div class="space-y-0">'

            .($step)(1,
                'Install the Ihsan script (once only)',
                'Copy the following code and paste it into your organisation\'s website, before the closing <code class="text-xs bg-zinc-100 px-1 rounded">&lt;/body&gt;</code> tag.'
            )
            .self::copyableSnippet('Script', $script)
            .'</div></div>'

            .($step)(2,
                'Add a donation button or link',
                'Place either of these codes on your website. When clicked, the donation form opens automatically as a pop-up.'
            )
            .self::copyableSnippet('Button', $button)
            .self::copyableSnippet('Link', $link)
            .'</div></div>'

            .($step)(3,
                'Register your website domain',
                'In the <strong>Allowed Domains</strong> section above, enter your organisation\'s website domain (e.g. <code class="text-xs bg-zinc-100 px-1 rounded">example.com</code>). The form will not open from unregistered domains.'
            )
            .'</div></div>'

            .'<div class="ml-11 rounded-lg bg-amber-50 border border-amber-200 p-3">'
            .'<p class="text-sm text-amber-700"><strong>Important:</strong> Make sure "Enable donation modal" is turned on and the domain is registered before using this code.</p>'
            .'</div>'

            .'</div>';
    }

    private static function copyableSnippet(string $label, string $code): string
    {
        return '<div x-data="{ copied: false }" data-code="'.e($code).'" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">'
            .'<div class="mb-2 flex items-center justify-between gap-3">'
            .'<span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">'.e($label).'</span>'
            .'<button type="button" x-on:click="navigator.clipboard.writeText($root.dataset.code).then(() => { copied = true; setTimeout(() => copied = false, 1500) })" class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100" x-text="copied ? \'Copied\' : \'Copy\'"></button>'
            .'</div>'
            .'<code class="block whitespace-pre-wrap break-all rounded-md bg-white p-2 text-xs text-zinc-700 ring-1 ring-zinc-200">'.e($code).'</code>'
            .'</div>';
    }

    private static function wordCountHint(mixed $state): string
    {
        $text = static::extractTextContent($state);
        $wordCount = str_word_count(strip_tags($text));
        $remaining = 100 - $wordCount;

        $color = $remaining >= 0 ? 'text-zinc-500' : 'text-danger-600';
        $weight = $remaining < 0 ? 'font-bold' : '';

        $label = "{$wordCount} / 100 words";

        if ($remaining < 0) {
            $label .= ' (over by '.abs($remaining).' words)';
        }

        return "<span class=\"{$color} {$weight} text-sm\">{$label}</span>";
    }

    private static function extractTextContent(mixed $content): string
    {
        if (is_string($content)) {
            return $content;
        }

        if (! is_array($content)) {
            return '';
        }

        if (isset($content['text'])) {
            return $content['text'];
        }

        $texts = [];

        if (isset($content['content'])) {
            foreach ($content['content'] as $node) {
                $texts[] = static::extractTextContent($node);
            }
        }

        return implode(' ', $texts);
    }
}
