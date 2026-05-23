<?php

namespace App\Filament\App\Resources\Campaigns\Schemas;

use App\Enums\CampaignStatus;
use App\Enums\PaymentGateway;
use App\Filament\Forms\Components\SuggestedAmounts;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
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
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Umum')
                                    ->description('Nama, status dan salinan kempen untuk penderma.')
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
                                            ->label('Pautan')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->columnSpanFull(),
                                        Select::make('status')
                                            ->label('Status')
                                            ->required()
                                            ->options(CampaignStatus::class),
                                        TextInput::make('headline')
                                            ->label('Tajuk utama')
                                            ->maxLength(255),
                                    ]),
                                Section::make('Kisah & media')
                                    ->description('Kandungan halaman kempen dan gambar utama.')
                                    ->schema([
                                        RichEditor::make('description')
                                            ->label('Penerangan')
                                            ->columnSpanFull()
                                            ->extraInputAttributes(['style' => 'min-height: 300px;'])
                                            ->live(debounce: 1500)
                                            ->hint(fn ($state): HtmlString => new HtmlString(static::wordCountHint($state))),
                                        FileUpload::make('image_path')
                                            ->label('Gambar utama')
                                            ->image()
                                            ->directory('campaigns')
                                            ->helperText('Saiz disyorkan: 1920 × 1060 px')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Section::make('Kekerapan')
                                    ->description('Kawal kekerapan derma dan jumlah khas.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('allow_recurring')
                                            ->label('Benarkan derma bulanan'),
                                        Toggle::make('allow_custom_amount')
                                            ->label('Benarkan jumlah khas'),
                                    ]),
                                Section::make('Jumlah')
                                    ->description('Tetapkan sasaran, jumlah minimum dan tempoh kempen.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('has_target')
                                            ->label('Tetapkan sasaran kutipan'),
                                        TextInput::make('target_amount')
                                            ->label('Jumlah sasaran')
                                            ->numeric()
                                            ->prefix('MYR')
                                            ->hidden(fn ($get) => ! $get('has_target')),
                                        TextInput::make('minimum_amount')
                                            ->label('Jumlah minimum')
                                            ->numeric()
                                            ->prefix('MYR'),
                                        DatePicker::make('end_date')
                                            ->label('Tarikh tamat')
                                            ->format('Y-m-d')
                                            ->displayFormat('d/m/Y'),
                                    ]),
                                Section::make('Jumlah disarankan')
                                    ->description('Butang jumlah tersedia di halaman checkout.')
                                    ->schema([
                                        SuggestedAmounts::make('suggested_amounts')
                                            ->label('Jumlah disarankan')
                                            ->columnSpanFull(),
                                    ]),
                                Section::make('Pembayaran')
                                    ->description('Pilih cara pemprosesan derma.')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('payment_gateway')
                                            ->label('Pemproses pembayaran')
                                            ->options(PaymentGateway::class)
                                            ->default(PaymentGateway::Stripe),
                                    ]),
                                Section::make('Pengalaman penyokong')
                                    ->description('Konfigurasi mesej selepas derma dan redirect.')
                                    ->columns(2)
                                    ->schema([
                                        Textarea::make('thank_you_message')
                                            ->label('Mesej terima kasih')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                        TextInput::make('redirect_url')
                                            ->label('URL redirect')
                                            ->url()
                                            ->placeholder('https://')
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Checkout Modal')
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Section::make('Benam modal')
                                    ->description('Tetapan untuk daftar masuk modal berdasarkan skrip di laman web klien.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('checkout_modal_enabled')
                                            ->label('Benarkan daftar masuk modal')
                                            ->helperText('Benarkan kempen ini dibuka dalam modal Ihsan.')
                                            ->default(false)
                                            ->columnSpanFull(),
                                        TextInput::make('form_parameter')
                                            ->label('Parameter borang')
                                            ->helperText('Contoh: MTMTDEVEFUND. Guna ?form=THIS dalam URL untuk buka daftar masuk modal.')
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->columnSpanFull(),
                                        TagsInput::make('checkout_allowed_domains')
                                            ->label('Domain dibenarkan')
                                            ->helperText('Hanya domain ini boleh buka kempen dalam modal benam.')
                                            ->placeholder('Tambah domain')
                                            ->columnSpanFull(),
                                        Placeholder::make('embed_modal_note')
                                            ->label('Pasang skrip')
                                            ->content(fn ($get) => new HtmlString(self::embedSnippetHtml($get('form_parameter'))))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Campaign Page')
                            ->icon('heroicon-o-globe-alt')
                            ->schema([
                                Section::make('Halaman dihoskan')
                                    ->description('Halaman kempen terus dan tetapan derma.')
                                    ->schema([
                                        Placeholder::make('hosted_page_note')
                                            ->label('Halaman derma')
                                            ->content(fn ($record) => new HtmlString(
                                                $record
                                                    ? 'Halaman terus: <code>'.e(url('/donate/'.$record->slug)).'</code>'
                                                    : 'Simpan kempen untuk menjana URL halaman derma.'
                                            ))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Stats')
                            ->icon('heroicon-o-chart-bar')
                            ->visible(fn ($record) => $record !== null)
                            ->columns(3)
                            ->schema([
                                Placeholder::make('collected_amount')
                                    ->label('Jumlah terkumpul')
                                    ->content(fn ($record) => new HtmlString(
                                        '<span class="text-2xl font-bold text-emerald-600">RM '.number_format($record->collected_amount ?? 0, 2).'</span>'
                                    )),
                                Placeholder::make('donation_count')
                                    ->label('Jumlah derma')
                                    ->content(fn ($record) => new HtmlString(
                                        '<span class="text-2xl font-bold text-zinc-900">'.$record->donations()->count().'</span>'
                                    )),
                                Placeholder::make('campaign_url')
                                    ->label('URL kempen')
                                    ->content(fn ($record) => new HtmlString(
                                        '<div x-data="{ copied: false, url: \''.e(url('/donate/'.$record->slug)).'\' }" class="flex items-center gap-2">'
                                        .'<code class="flex-1 truncate text-sm text-zinc-600" x-text="url"></code>'
                                        .'<button type="button" x-on:click="navigator.clipboard.writeText(url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" class="shrink-0 rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:bg-zinc-200" x-text="copied ? \'Disalin!\' : \'Salin\'"></button>'
                                        .'</div>'
                                    ))
                                    ->columnSpan(2),
                                Placeholder::make('progress_bar')
                                    ->label('Prestasi kempen')
                                    ->columnSpanFull()
                                    ->content(fn ($record) => new HtmlString(
                                        static::progressBarHtml($record)
                                    )),
                            ]),
                    ]),
            ]);
    }

    private static function progressBarHtml($record): string
    {
        if (! $record || ! $record->has_target || ! $record->target_amount) {
            return '<p style="font-size: 0.875rem; color: #71717a;">Tiada sasaran ditetapkan untuk kempen ini.</p>';
        }

        $collected = (float) $record->collected_amount;
        $target = (float) $record->target_amount;
        $percentage = min(round(($collected / $target) * 100, 1), 100);
        $barWidth = max($percentage, 2);
        $barColor = $percentage >= 100 ? '#10b981' : ($percentage >= 50 ? '#34d399' : '#f59e0b');

        return '<div style="display: flex; flex-direction: column; gap: 0.75rem;">'
            .'<div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.875rem; line-height: 1.25rem;">'
            .'<span style="font-weight: 500; color: #18181b;">RM '.number_format($collected, 2).'</span>'
            .'<span style="color: #71717a;">daripada RM '.number_format($target, 2).'</span>'
            .'</div>'
            .'<div style="height: 20px; width: 100%; overflow: hidden; border-radius: 9999px; background-color: #e4e4e7;">'
            .'<div style="height: 100%; border-radius: 9999px; transition: all 0.5s; width: '.$barWidth.'%; background-color: '.$barColor.';"></div>'
            .'</div>'
            .'<div style="display: flex; align-items: center; justify-content: space-between; font-size: 0.75rem; line-height: 1rem;">'
            .'<span style="font-weight: 600; color: #3f3f46;">'.$percentage.'% terkumpul</span>'
            .'<span style="color: #71717a;">'.($percentage >= 100 ? 'Sasaran tercapai!' : 'Baki: RM '.number_format(max($target - $collected, 0), 2)).'</span>'
            .'</div>'
            .'</div>';
    }

    private static function embedSnippetHtml(?string $formParameter): string
    {
        $formParameter = filled($formParameter) ? $formParameter : 'FORM_PARAMETER';

        $script = '<script src="'.url('/embed.js').'" async></script>';
        $button = '<button data-ihsan-form="'.$formParameter.'">Donate</button>';
        $link = '<a href="?form='.$formParameter.'">Donate</a>';

        return '<div class="space-y-3">'
            .'<p class="text-sm text-zinc-600">Letakkan skrip sekali di laman web klien, kemudian guna butang atau pautan untuk buka kempen ini sebagai daftar masuk modal.</p>'
            .self::copyableSnippet('Skrip', $script)
            .self::copyableSnippet('Butang', $button)
            .self::copyableSnippet('Pautan', $link)
            .'<p class="text-xs text-zinc-500">Senarai domain dibenarkan akan dikuatkuasakan oleh Ihsan sebelum borang checkout dibuka.</p>'
            .'</div>';
    }

    private static function copyableSnippet(string $label, string $code): string
    {
        return '<div x-data="{ copied: false, code: '.Js::from($code).' }" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">'
            .'<div class="mb-2 flex items-center justify-between gap-3">'
            .'<span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">'.e($label).'</span>'
            .'<button type="button" x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 1500) })" class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100" x-text="copied ? \'Disalin\' : \'Salin\'"></button>'
            .'</div>'
            .'<code class="block overflow-x-auto whitespace-pre rounded-md bg-white p-2 text-xs text-zinc-700 ring-1 ring-zinc-200">'.e($code).'</code>'
            .'</div>';
    }

    private static function wordCountHint(mixed $state): string
    {
        $text = static::extractTextContent($state);
        $wordCount = str_word_count(strip_tags($text));
        $remaining = 100 - $wordCount;

        $color = $remaining >= 0 ? 'text-zinc-500' : 'text-danger-600';
        $weight = $remaining < 0 ? 'font-bold' : '';

        $label = "{$wordCount} / 100 patah perkataan";

        if ($remaining < 0) {
            $label .= " (lebih {$remaining} patah perkataan)";
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
