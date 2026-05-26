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
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
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
                                        FileUpload::make('image_path')
                                            ->label('Gambar utama')
                                            ->image()
                                            ->directory('campaigns')
                                            ->helperText('Saiz disyorkan: 1920 × 1060 px')
                                            ->columnSpanFull(),
                                        RichEditor::make('description')
                                            ->label('Penerangan')
                                            ->columnSpanFull()
                                            ->extraInputAttributes(['style' => 'min-height: 300px;'])
                                            ->live(debounce: 1500)
                                            ->hint(fn ($state): HtmlString => new HtmlString(static::wordCountHint($state))),
                                    ]),
                            ]),
                        Tab::make('Settings')
                            ->icon('heroicon-o-cog-6-tooth')
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        Section::make('Kekerapan')
                                            ->description('Kawal kekerapan derma dan jumlah khas.')
                                            ->columnSpan(1)
                                            ->columns(2)
                                            ->schema([
                                                Toggle::make('allow_recurring')
                                                    ->label('Benarkan derma bulanan'),
                                                Toggle::make('allow_custom_amount')
                                                    ->label('Benarkan jumlah khas'),
                                                TextInput::make('minimum_amount')
                                                    ->label('Jumlah minima')
                                                    ->helperText('Jumlah minimum untuk setiap derma.')
                                                    ->numeric()
                                                    ->min(0)
                                                    ->step(1)
                                                    ->default(5)
                                                    ->columnSpanFull(),
                                            ]),
                                        Section::make('Jumlah')
                                            ->description('Tetapkan sasaran dan tempoh kempen.')
                                            ->columnSpan(1)
                                            ->columns(2)
                                            ->schema([
                                                Toggle::make('has_target')
                                                    ->label('Tetapkan sasaran kutipan')
                                                    ->live()
                                                    ->columnSpanFull(),
                                                TextInput::make('target_amount')
                                                    ->label('Jumlah sasaran')
                                                    ->numeric()
                                                    ->prefix('MYR')
                                                    ->disabled(fn ($get) => ! $get('has_target'))
                                                    ->columnSpan(1),
                                                DatePicker::make('end_date')
                                                    ->label('Tarikh tamat')
                                                    ->format('Y-m-d')
                                                    ->displayFormat('d/m/Y'),
                                            ]),
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
                                Section::make('Konfigurasi')
                                    ->description('Aktifkan modal derma dan hadkan domain yang dibenarkan.')
                                    ->columns(2)
                                    ->schema([
                                        Toggle::make('checkout_modal_enabled')
                                            ->label('Aktifkan modal derma')
                                            ->helperText('Penderma boleh buka borang ini sebagai pop-up terus dari laman web organisasi.')
                                            ->default(false)
                                            ->columnSpanFull(),
                                        TextInput::make('form_parameter')
                                            ->label('Kod kempen')
                                            ->helperText('Kod unik untuk kempen ini. Dijana secara automatik — boleh ditukar.')
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true),
                                        TagsInput::make('checkout_allowed_domains')
                                            ->label('Domain dibenarkan')
                                            ->helperText('Masukkan domain laman web organisasi, contoh: abc.com — Borang tidak akan dibuka dari domain lain.')
                                            ->placeholder('Tambah domain')
                                            ->default(function (): array {
                                                $org = auth()->user()->organization;
                                                $domains = $org?->settings['allowed_domains'] ?? [];

                                                if (! empty($domains)) {
                                                    return $domains;
                                                }

                                                if ($org?->website_url) {
                                                    $host = parse_url($org->website_url, PHP_URL_HOST);
                                                    if ($host) {
                                                        return [$host];
                                                    }
                                                }

                                                return [];
                                            }),
                                    ]),
                                Section::make('Cara pasang di laman web')
                                    ->description('Ikuti langkah di bawah untuk benamkan borang derma di laman web organisasi.')
                                    ->schema([
                                        Placeholder::make('embed_modal_note')
                                            ->hiddenLabel()
                                            ->content(fn ($get) => new HtmlString(self::embedSnippetHtml($get('form_parameter'))))
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Campaign Page')
                            ->icon('heroicon-o-globe-alt')
                            ->visible(fn ($record) => $record !== null)
                            ->schema([
                                Grid::make()
                                    ->columns(2)
                                    ->schema([
                                        Section::make('Pautan kempen')
                                            ->description('URL terus, QR code dan pautan kongsi untuk kempen ini.')
                                            ->columnSpan(1)
                                            ->schema([
                                                Placeholder::make('hosted_page_note')
                                                    ->hiddenLabel()
                                                    ->content(fn ($record) => new HtmlString(
                                                        $record
                                                            ? static::campaignPageShareHtml($record)
                                                            : '<p class="text-sm text-zinc-500">Simpan kempen untuk menjana URL halaman derma.</p>'
                                                    ))
                                                    ->columnSpanFull(),
                                            ]),
                                        Section::make('Benam dalam laman web')
                                            ->description('Borang derma terus dalam halaman — tanpa pop-up.')
                                            ->columnSpan(1)
                                            ->schema([
                                                Placeholder::make('inline_embed_note')
                                                    ->hiddenLabel()
                                                    ->content(fn ($record) => new HtmlString(
                                                        $record
                                                            ? static::inlineEmbedHtml($record)
                                                            : '<p class="text-sm text-zinc-500">Simpan kempen untuk mendapatkan kod benam.</p>'
                                                    ))
                                                    ->columnSpanFull(),
                                            ]),
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
                                        static::donateUrlsCopyableHtml($record)
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

    private static function campaignPageShareHtml($record): string
    {
        $elements = $record->elements;

        if ($elements->isEmpty()) {
            if (! $record->checkout_modal_enabled) {
                return '<p class="text-sm text-zinc-500">Tiada element dikaitkan. Cipta element terlebih dahulu, atau aktifkan modal derma untuk pautan terus.</p>';
            }

            return self::shareableUrlHtml(route('donations.campaign-show', ['campaign' => $record->form_parameter]));
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
            .'<button type="button" x-on:click="navigator.clipboard.writeText($root.dataset.url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100 transition" x-text="copied ? \'Disalin!\' : \'Salin\'"></button>'
            .'<a href="'.e($url).'" target="_blank" rel="noopener" class="shrink-0 rounded-lg bg-white px-3 py-1.5 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100 transition">Buka →</a>'
            .'</div>'
            .'<div class="flex items-start gap-6">'
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
            if (! $record->checkout_modal_enabled) {
                return '<p class="text-sm text-zinc-500">Tiada element — cipta element terlebih dahulu, atau aktifkan modal derma untuk benaman.</p>';
            }

            $url = route('donations.campaign-show', ['campaign' => $record->form_parameter]);
            $iframe = self::embedIframeHtml($url, $record->form_parameter);

            return '<p class="text-sm text-zinc-500 mb-4">Letak kod berikut dalam mana-mana halaman web untuk benamkan borang derma terus tanpa pop-up.</p>'
                .self::copyableSnippet('Kod benam', $iframe)
                .'<p class="text-xs text-zinc-400 mt-3">Laraskan <code class="bg-zinc-100 px-1 rounded">height</code> mengikut keperluan. Nilai <code class="bg-zinc-100 px-1 rounded">700</code> sesuai untuk kebanyakan skrin.</p>';
        }

        $parts = ['<p class="text-sm text-zinc-500 mb-4">Letak kod berikut dalam mana-mana halaman web untuk benamkan borang derma terus tanpa pop-up.</p>'];

        foreach ($elements as $element) {
            $url = route('donations.show', $element);
            $iframe = self::embedIframeHtml($url, $element->token);
            $parts[] = self::copyableSnippet('Kod benam', $iframe);
        }

        $parts[] = '<p class="text-xs text-zinc-400 mt-3">Laraskan <code class="bg-zinc-100 px-1 rounded">height</code> mengikut keperluan. Nilai <code class="bg-zinc-100 px-1 rounded">700</code> sesuai untuk kebanyakan skrin.</p>';

        return implode('', $parts);
    }

    private static function donateUrlsCopyableHtml($record): string
    {
        $elements = $record->elements;

        if ($elements->isEmpty()) {
            if (! $record->checkout_modal_enabled) {
                return '<p class="text-sm text-zinc-500">Tiada URL — kempen belum ada element atau modal derma tidak diaktifkan.</p>';
            }

            $base = route('donations.campaign-show', ['campaign' => $record->form_parameter]);

            return self::labeledUrlRow('Wide', $base.'?popup=1')
                .self::labeledUrlRow('Compact', $base);
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
            .'<button type="button" x-on:click="navigator.clipboard.writeText($root.dataset.url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })" class="shrink-0 rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:bg-zinc-200" x-text="copied ? \'Disalin!\' : \'Salin\'"></button>'
            .'<a href="'.e($url).'" target="_blank" rel="noopener" class="shrink-0 rounded-lg bg-zinc-100 px-3 py-1.5 text-xs font-medium text-zinc-600 transition hover:bg-zinc-200">Buka →</a>'
            .'</div>';
    }

    private static function embedSnippetHtml(?string $formParameter): string
    {
        $formParameter = filled($formParameter) ? $formParameter : 'FORM_PARAMETER';

        $script = '<script src="'.url('/embed.js').'" async></script>';
        $button = '<button data-ihsan-form="'.$formParameter.'" style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:#0d9488;color:#fff;font:600 15px/1 system-ui,sans-serif;border:0;border-radius:10px;cursor:pointer;box-shadow:0 2px 6px rgba(13,148,136,.3);transition:background .15s,transform .15s;letter-spacing:.01em" onmouseover="this.style.background=\'#0f766e\'" onmouseout="this.style.background=\'#0d9488\'" onmousedown="this.style.transform=\'scale(.97)\'" onmouseup="this.style.transform=\'\'">Derma</button>';
        $link = '<a href="?form='.$formParameter.'" style="display:inline-flex;align-items:center;gap:8px;padding:12px 28px;background:#0d9488;color:#fff;font:600 15px/1 system-ui,sans-serif;border-radius:10px;text-decoration:none;box-shadow:0 2px 6px rgba(13,148,136,.3);transition:background .15s,transform .15s;letter-spacing:.01em" onmouseover="this.style.background=\'#0f766e\'" onmouseout="this.style.background=\'#0d9488\'" onmousedown="this.style.transform=\'scale(.97)\'" onmouseup="this.style.transform=\'\'">Derma</a>';

        $step = fn (int $n, string $title, string $body) => '<div class="flex gap-4">'
            .'<div class="shrink-0 flex size-7 items-center justify-center rounded-full bg-teal-600 text-white text-xs font-bold">'.$n.'</div>'
            .'<div class="space-y-2 flex-1 pb-5">'
            .'<p class="text-sm font-semibold text-zinc-800">'.$title.'</p>'
            .'<p class="text-sm text-zinc-500">'.$body.'</p>';

        return '<div class="space-y-0">'

            .($step)(1,
                'Pasang skrip Ihsan (sekali sahaja)',
                'Salin kod berikut dan letak dalam laman web organisasi, sebelum penutup tag <code class="text-xs bg-zinc-100 px-1 rounded">&lt;/body&gt;</code>.'
            )
            .self::copyableSnippet('Skrip', $script)
            .'</div></div>'

            .($step)(2,
                'Tambah butang atau pautan derma',
                'Letak mana-mana satu kod ini di laman web. Apabila diklik, borang derma terbuka sebagai pop-up secara automatik.'
            )
            .self::copyableSnippet('Butang', $button)
            .self::copyableSnippet('Pautan', $link)
            .'</div></div>'

            .($step)(3,
                'Daftarkan domain laman web',
                'Dalam bahagian <strong>Domain dibenarkan</strong> di atas, masukkan domain laman web organisasi (contoh: <code class="text-xs bg-zinc-100 px-1 rounded">mumzatuttaqwa.com</code>). Borang tidak akan buka dari domain yang tidak didaftarkan.'
            )
            .'</div></div>'

            .'<div class="ml-11 rounded-lg bg-amber-50 border border-amber-200 p-3">'
            .'<p class="text-sm text-amber-700"><strong>Penting:</strong> Pastikan "Aktifkan modal derma" dihidupkan dan domain sudah didaftarkan sebelum menggunakan kod ini.</p>'
            .'</div>'

            .'</div>';
    }

    private static function copyableSnippet(string $label, string $code): string
    {
        return '<div x-data="{ copied: false }" data-code="'.e($code).'" class="rounded-lg border border-zinc-200 bg-zinc-50 p-3">'
            .'<div class="mb-2 flex items-center justify-between gap-3">'
            .'<span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">'.e($label).'</span>'
            .'<button type="button" x-on:click="navigator.clipboard.writeText($root.dataset.code).then(() => { copied = true; setTimeout(() => copied = false, 1500) })" class="rounded-md bg-white px-2.5 py-1 text-xs font-medium text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100" x-text="copied ? \'Disalin\' : \'Salin\'"></button>'
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
