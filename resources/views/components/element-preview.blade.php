@php
    $type = $type ?? null;
    $type = $type instanceof \BackedEnum ? $type->value : $type;
    $config = $config ?? [];
    $defaultAmountsOneTime = collect($config['suggested_amounts_one_time'] ?? $config['suggested_amounts'] ?? [200, 100, 50, 30, 10, 5])
        ->map(fn ($amount) => (int) $amount)
        ->filter(fn (int $amount) => $amount > 0)
        ->values()
        ->all();
    $defaultAmountsMonthly = collect($config['suggested_amounts_monthly'] ?? $config['suggested_amounts'] ?? [100, 50, 30, 20, 10, 5])
        ->map(fn ($amount) => (int) $amount)
        ->filter(fn (int $amount) => $amount > 0)
        ->values()
        ->all();
    $title = $config['title'] ?? $config['heading'] ?? null;
    $defaultAmount = (int) ($config['default_amount'] ?? ($defaultAmountsOneTime[0] ?? 5));
    $defaultFrequency = $config['default_frequency'] ?? 'monthly';
    $allowMonthly = $config['allow_monthly'] ?? true;
    $showSuggested = $config['show_suggested'] ?? true;
    $showAmountInput = $config['show_amount_input'] ?? true;
    $showDedication = $config['show_dedication'] ?? true;
    $showComment = $config['show_comment'] ?? true;
    $previewFrequency = $allowMonthly ? $defaultFrequency : 'one_time';
    $effectGradients = [
        'gradient_teal_green'    => 'linear-gradient(120deg,#0d9488,#14b8a6,#22c55e,#0d9488)',
        'gradient_blue_purple'   => 'linear-gradient(120deg,#2563eb,#7c3aed,#a855f7,#2563eb)',
        'gradient_orange_red'    => 'linear-gradient(120deg,#ea580c,#dc2626,#f97316,#ea580c)',
        'gradient_rose_pink'     => 'linear-gradient(120deg,#f43f5e,#ec4899,#fb7185,#f43f5e)',
        'gradient_amber_orange'  => 'linear-gradient(120deg,#f59e0b,#ea580c,#fbbf24,#f59e0b)',
        'gradient_cyan_blue'     => 'linear-gradient(120deg,#06b6d4,#2563eb,#38bdf8,#06b6d4)',
        'gradient_emerald_teal'  => 'linear-gradient(120deg,#10b981,#0d9488,#34d399,#10b981)',
        'gradient_indigo_purple' => 'linear-gradient(120deg,#6366f1,#7c3aed,#818cf8,#6366f1)',
        'gradient_gold_amber'    => 'linear-gradient(120deg,#eab308,#f59e0b,#fcd34d,#eab308)',
        'gradient_pink_purple'   => 'linear-gradient(120deg,#ec4899,#a855f7,#f472b6,#ec4899)',
    ];
    $effectShadows = [
        'gradient_teal_green'    => '0 8px 20px rgba(13,148,136,.4)',
        'gradient_blue_purple'   => '0 8px 20px rgba(37,99,235,.4)',
        'gradient_orange_red'    => '0 8px 20px rgba(234,88,12,.4)',
        'gradient_rose_pink'     => '0 8px 20px rgba(244,63,94,.4)',
        'gradient_amber_orange'  => '0 8px 20px rgba(245,158,11,.4)',
        'gradient_cyan_blue'     => '0 8px 20px rgba(6,182,212,.4)',
        'gradient_emerald_teal'  => '0 8px 20px rgba(16,185,129,.4)',
        'gradient_indigo_purple' => '0 8px 20px rgba(99,102,241,.4)',
        'gradient_gold_amber'    => '0 8px 20px rgba(234,179,8,.4)',
        'gradient_pink_purple'   => '0 8px 20px rgba(236,72,153,.4)',
    ];
@endphp

<div
    wire:key="element-preview-{{ md5(json_encode([$type, $config])) }}"
    class="rounded-xl border border-zinc-200 bg-white"
>
    <div class="border-b border-zinc-100 px-4 py-2">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Preview</h3>
            @if($type)
                @php
                    $typeLabel = $type instanceof \BackedEnum
                        ? $type->label()
                        : (\App\Enums\ElementType::tryFrom($type)?->label() ?? ucwords(str_replace('_', ' ', $type)));
                @endphp
                <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600">
                    {{ $typeLabel }}
                </span>
            @endif
        </div>
    </div>

    <div class="flex min-h-[180px] items-center justify-center bg-zinc-50 px-4 py-6">
        @if(! $type)
            <div class="text-center">
                <svg class="mx-auto size-10 text-zinc-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                </svg>
                <p class="mt-3 text-sm text-zinc-400">Select a type to preview</p>
            </div>

        @elseif($type === 'button')
            @php
                $btnEffect = $config['button_effect'] ?? 'none';
                $hasEffect = $btnEffect !== 'none' && isset($effectGradients[$btnEffect]);
                $effectId  = 'btn-' . md5($btnEffect);
            @endphp
            @if($hasEffect)
                <style>
                    @keyframes ihsan-gradient-{{ $effectId }} {
                        0%   { background-position: 0% 50%; }
                        50%  { background-position: 100% 50%; }
                        100% { background-position: 0% 50%; }
                    }
                    .ihsan-effect-{{ $effectId }} {
                        background: {{ $effectGradients[$btnEffect] }};
                        background-size: 300% 300%;
                        animation: ihsan-gradient-{{ $effectId }} 4s ease infinite;
                        box-shadow: {{ $effectShadows[$btnEffect] }};
                        transition: transform .2s ease, box-shadow .2s ease;
                    }
                    .ihsan-effect-{{ $effectId }}:hover {
                        transform: translateY(-2px);
                        box-shadow: {{ str_replace('.4)', '.55)', $effectShadows[$btnEffect]) }};
                    }
                    .ihsan-effect-{{ $effectId }}:active { transform: scale(0.97); }
                </style>
            @endif
            <div class="flex min-h-[120px] items-center justify-center">
                @php
                    $sizeMap = [
                        'small'  => ['padding' => '8px 16px', 'font' => '14px'],
                        'medium' => ['padding' => '12px 24px', 'font' => '16px'],
                        'large'  => ['padding' => '16px 32px', 'font' => '18px'],
                    ];
                    $sz = $sizeMap[$config['button_size'] ?? 'medium'] ?? $sizeMap['medium'];
                    $colorClass = $config['button_color'] ?? 'bg-blue-600 hover:bg-blue-700';
                    $btnClass = $hasEffect ? "ihsan-effect-{$effectId}" : $colorClass;
                    $buttonIcon = $config['button_icon'] ?? 'heart';
                    $buttonIcons = [
                        'heart' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
                        'hand' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M23 12.22V15c0 4.97-4.03 9-9 9H9.17c-1.59 0-3.11-.63-4.24-1.76L0 17.5l1.5-1.5c.47-.47 1.08-.73 1.77-.73.46 0 .9.12 1.28.35L7 17.34V4.5C7 3.67 7.67 3 8.5 3S10 3.67 10 4.5v4h1V3.5C11 2.67 11.67 2 12.5 2S14 2.67 14 3.5V8.5h1V4c0-.83.67-1.5 1.5-1.5S18 3.17 18 4v5.5h1V6c0-.83.67-1.5 1.5-1.5S22 5.17 22 6v6.22z"/></svg>',
                        'star' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
                        'gift' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 12 7.01l3.38 4.99L17 10.83 14.92 8H20v6z"/></svg>',
                        'plus' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
                    ];
                    $buttonIconSvg = $buttonIcon !== 'none' ? ($buttonIcons[$buttonIcon] ?? $buttonIcons['heart']) : '';
                @endphp
                <button
                    type="button"
                    class="{{ $btnClass }} inline-flex items-center justify-center gap-2 font-semibold text-white focus:outline-none"
                    style="padding: {{ $sz['padding'] }}; font-size: {{ $sz['font'] }}; border-radius: {{ ($config['corner_radius'] ?? 8) }}px"
                >
                    {!! $buttonIconSvg !!}
                    <span>{{ $config['button_text'] ?? 'Donate Now' }}</span>
                </button>
            </div>

        @elseif($type === 'qr_code')
            @php
                $qrSize = is_numeric($config['size'] ?? null) ? (int) $config['size'] : match(strtolower($config['size'] ?? 'medium')) {
                    'small' => 150,
                    'medium' => 200,
                    'large' => 250,
                    'extra large' => 300,
                    default => 200,
                };
                $qrAlignment = $config['alignment'] ?? 'center';
                $qrLabel = filled($config['label'] ?? null)
                    ? $config['label']
                    : (filled($config['button_text'] ?? null) ? $config['button_text'] : 'Scan to donate');
                $alignClass = match($qrAlignment) {
                    'left' => 'text-left',
                    'right' => 'text-right',
                    default => 'text-center',
                };
                $qrTitle = $config['title'] ?? '';
                $qrMessage = $config['message'] ?? '';
                $qrDataUrl = $config['qr_url'] ?? config('app.url') . '/donate/demo';
            @endphp
            <div class="flex min-h-[180px] flex-col items-center justify-center w-full">
                <div class="{{ $alignClass }} space-y-3">
                    @if(filled($qrTitle))
                        <h4 class="text-base font-semibold text-zinc-800">{{ $qrTitle }}</h4>
                    @endif
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size={{ $qrSize }}x{{ $qrSize }}&data={{ urlencode($qrDataUrl) }}&bgcolor=ffffff&color=0f172a&qzone=2"
                        alt="QR Code"
                        width="{{ $qrSize }}"
                        height="{{ $qrSize }}"
                        class="inline-block rounded-xl border border-zinc-200"
                    >
                    @if(filled($qrLabel))
                        <p class="text-sm font-medium text-zinc-600">{{ $qrLabel }}</p>
                    @endif
                    @if(filled($qrMessage))
                        <p class="text-sm text-zinc-500">{{ $qrMessage }}</p>
                    @endif
                </div>
            </div>

        @elseif($type === 'floating_button')
            @php
                $colors = [
                    'campaign' => '#16a34a',
                    'blue' => '#2563eb', 'teal' => '#0d9488', 'green' => '#16a34a',
                    'orange' => '#ea580c', 'red' => '#dc2626', 'purple' => '#9333ea', 'dark' => '#1e293b',
                ];
                $fbEffect = $config['button_effect'] ?? 'none';
                $fbHasEffect = $fbEffect !== 'none' && isset($effectGradients[$fbEffect]);
                $fbEffectId = 'fb-' . md5($fbEffect);
                $previewColor = $colors[$config['color'] ?? 'campaign'] ?? '#16a34a';
                $sizes = [
                    'small' => ['size' => '48px', 'font' => '12px'],
                    'medium' => ['size' => '56px', 'font' => '14px'],
                    'large' => ['size' => '64px', 'font' => '16px'],
                ];
                $sz = $sizes[strtolower($config['size'] ?? 'medium')] ?? $sizes['medium'];
                $shapes = ['pill' => '9999px', 'circle' => '50%', 'square' => '0', 'rounded' => '12px'];
                $radius = $shapes[$config['shape'] ?? 'pill'] ?? '9999px';
                $isPill = ($config['shape'] ?? 'pill') === 'pill';
                $icons = [
                    'heart' => '<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>',
                    'hand' => '<path d="M23 12.22V15c0 4.97-4.03 9-9 9H9.17c-1.59 0-3.11-.63-4.24-1.76L0 17.5l1.5-1.5c.47-.47 1.08-.73 1.77-.73.46 0 .9.12 1.28.35L7 17.34V4.5C7 3.67 7.67 3 8.5 3S10 3.67 10 4.5v4h1V3.5C11 2.67 11.67 2 12.5 2S14 2.67 14 3.5V8.5h1V4c0-.83.67-1.5 1.5-1.5S18 3.17 18 4v5.5h1V6c0-.83.67-1.5 1.5-1.5S22 5.17 22 6v6.22z"/>',
                    'star' => '<path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>',
                    'gift' => '<path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 12 7.01l3.38 4.99L17 10.83 14.92 8H20v6z"/>',
                    'plus' => '<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>',
                ];
                $iconPath = $icons[$config['icon'] ?? 'heart'] ?? $icons['heart'];
            @endphp
            @if($fbHasEffect)
                <style>
                    @keyframes ihsan-gradient-{{ $fbEffectId }} {
                        0%   { background-position: 0% 50%; }
                        50%  { background-position: 100% 50%; }
                        100% { background-position: 0% 50%; }
                    }
                    .ihsan-effect-{{ $fbEffectId }} {
                        background: {{ $effectGradients[$fbEffect] }};
                        background-size: 300% 300%;
                        animation: ihsan-gradient-{{ $fbEffectId }} 4s ease infinite;
                        box-shadow: {{ $effectShadows[$fbEffect] }};
                    }
                </style>
            @endif
            <div class="relative flex min-h-[220px] w-full items-center justify-center rounded-lg border-2 border-dashed border-zinc-200 bg-white/60 p-6">
                <div
                    class="ihsan-effect-{{ $fbHasEffect ? $fbEffectId : '' }} inline-flex items-center justify-center gap-3 text-white shadow-2xl transition-transform hover:scale-105"
                    style="{{ $fbHasEffect ? '' : 'background:' . $previewColor . ';' }}width:{{ $isPill ? 'auto' : $sz['size'] }};height:{{ $sz['size'] }};font-size:{{ $sz['font'] }};border-radius:{{ $radius }};{{ $isPill ? 'padding:0 32px;min-width:'.intval($sz['size'])*1.2 . 'px' : '' }}"
                >
                    <svg viewBox="0 0 24 24" fill="currentColor" width="24" height="24">{!! $iconPath !!}</svg>
                    @if(($config['shape'] ?? 'pill') === 'pill')
                        <span style="font-weight:700;white-space:nowrap;font-size:{{ $sz['font'] }}">
                            {{ $config['button_text'] ?? 'Donate Now' }}
                        </span>
                    @endif
                </div>
                
                <span class="absolute bottom-2 text-xs text-zinc-400">Preview shown centered — on site it floats at configured position</span>
            </div>

        @elseif($type === 'sticky_button')
            @php
                $sbColorMap = [
                    'blue' => '#2563eb', 'teal' => '#0d9488', 'green' => '#16a34a',
                    'orange' => '#ea580c', 'red' => '#dc2626', 'purple' => '#9333ea', 'dark' => '#1e293b',
                ];
                $sbEffect = $config['button_effect'] ?? $config['effect'] ?? 'none';
                $sbHasEffect = $sbEffect !== 'none' && isset($effectGradients[$sbEffect]);
                $sbEffectId = 'sb-' . md5($sbEffect);

                $sbColor = '#16a34a';
                $sbRawColor = $config['button_color'] ?? $config['color'] ?? 'campaign';
                if ($sbRawColor[0] === '#') {
                    $sbColor = $sbRawColor;
                } elseif (str_contains($sbRawColor, 'blue')) {
                    $sbColor = '#2563eb';
                } elseif (str_contains($sbRawColor, 'teal')) {
                    $sbColor = '#0d9488';
                } elseif (str_contains($sbRawColor, 'green')) {
                    $sbColor = '#16a34a';
                } elseif (str_contains($sbRawColor, 'orange')) {
                    $sbColor = '#ea580c';
                } elseif (str_contains($sbRawColor, 'red')) {
                    $sbColor = '#dc2626';
                } elseif (str_contains($sbRawColor, 'purple')) {
                    $sbColor = '#9333ea';
                } elseif (str_contains($sbRawColor, 'dark') || str_contains($sbRawColor, 'gray') || str_contains($sbRawColor, 'slate')) {
                    $sbColor = '#1e293b';
                }
                $sbColor = $sbColorMap[strtolower($sbRawColor)] ?? $sbColor;

                $sbPosition = $config['position'] ?? 'right-center';
                $sbIcons = [
                    'heart' => '<path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>',
                    'hand' => '<path d="M23 12.22V15c0 4.97-4.03 9-9 9H9.17c-1.59 0-3.11-.63-4.24-1.76L0 17.5l1.5-1.5c.47-.47 1.08-.73 1.77-.73.46 0 .9.12 1.28.35L7 17.34V4.5C7 3.67 7.67 3 8.5 3S10 3.67 10 4.5v4h1V3.5C11 2.67 11.67 2 12.5 2S14 2.67 14 3.5V8.5h1V4c0-.83.67-1.5 1.5-1.5S18 3.17 18 4v5.5h1V6c0-.83.67-1.5 1.5-1.5S22 5.17 22 6v6.22z"/>',
                    'star' => '<path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>',
                    'gift' => '<path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 12 7.01l3.38 4.99L17 10.83 14.92 8H20v6z"/>',
                    'plus' => '<path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/>',
                ];
                $sbIconPath = $sbIcons[$config['button_icon'] ?? $config['icon'] ?? 'heart'] ?? $sbIcons['heart'];
                $sbRotation = str_starts_with($sbPosition, 'left') ? 'rotate(90deg)' : 'rotate(-90deg)';
                $sbBorderRadius = str_starts_with($sbPosition, 'left') ? '0 12px 12px 0' : '12px 0 0 12px';
                $sbText = $config['button_text'] ?? 'Donate';
                $sbCharCount = mb_strlen($sbText);
                // Estimate height needed for rotated text at 15px + 0.08em letter-spacing (~10px/char)
                $sbMinHeight = max(120, ($sbCharCount * 10) + 60);
            @endphp
            @if($sbHasEffect)
                <style>
                    @keyframes ihsan-gradient-{{ $sbEffectId }} {
                        0%   { background-position: 0% 50%; }
                        50%  { background-position: 100% 50%; }
                        100% { background-position: 0% 50%; }
                    }
                    .ihsan-effect-{{ $sbEffectId }} {
                        background: {{ $effectGradients[$sbEffect] }};
                        background-size: 300% 300%;
                        animation: ihsan-gradient-{{ $sbEffectId }} 4s ease infinite;
                        box-shadow: {{ $effectShadows[$sbEffect] }};
                    }
                </style>
            @endif
            <div class="relative flex min-h-[300px] w-full items-center justify-center rounded-lg border-2 border-dashed border-zinc-200 bg-white/60 p-6">
                <div
                    class="ihsan-effect-{{ $sbHasEffect ? $sbEffectId : '' }} relative inline-flex items-center justify-center text-white shadow-2xl transition-transform hover:scale-105"
                    style="{{ $sbHasEffect ? '' : 'background:' . $sbColor . ';' }}padding:14px 0;border-radius:{{ $sbBorderRadius }};width:52px;min-height:{{ $sbMinHeight }}px;"
                >
                    <div class="absolute left-1/2 top-1/2 inline-flex items-center justify-center gap-1.5" style="transform:translate(-50%,-50%) {{ $sbRotation }};white-space:nowrap;font-size:15px;font-weight:700;letter-spacing:.08em;">
                        <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">{!! $sbIconPath !!}</svg>
                        <span>{{ $sbText }}</span>
                    </div>
                </div>
                <span class="absolute bottom-2 text-xs text-zinc-400">Preview shown centered — on site it sticks to the {{ str_starts_with($sbPosition, 'left') ? 'left' : 'right' }} edge. Max 16 characters.</span>
            </div>

        @elseif($type === 'link')
            @php
                $linkColors = [
                    'campaign' => '#16a34a', 'blue' => '#2563eb', 'teal' => '#0d9488',
                    'green' => '#16a34a', 'orange' => '#ea580c', 'red' => '#dc2626',
                    'purple' => '#9333ea', 'dark' => '#1e293b',
                ];
                $btnColorClass = $config['button_color'] ?? '';
                $linkColor = match (true) {
                    str_contains($btnColorClass, 'blue') => '#2563eb',
                    str_contains($btnColorClass, 'teal') => '#0d9488',
                    str_contains($btnColorClass, 'green') => '#16a34a',
                    str_contains($btnColorClass, 'orange') => '#ea580c',
                    str_contains($btnColorClass, 'red') => '#dc2626',
                    str_contains($btnColorClass, 'purple') => '#9333ea',
                    str_contains($btnColorClass, 'gray') => '#1e293b',
                    default => $linkColors[$config['color'] ?? 'campaign'] ?? '#16a34a',
                };
                $linkStyle = $config['style'] ?? 'button';
                $linkText = $config['text'] ?? $config['button_text'] ?? 'Donate';
                $linkAlignment = $config['alignment'] ?? 'center';
                $linkSizes = [
                    'small' => ['font' => '14px', 'padding' => '8px 16px'],
                    'medium' => ['font' => '16px', 'padding' => '10px 24px'],
                    'large' => ['font' => '18px', 'padding' => '12px 32px'],
                ];
                $sizeKey = strtolower($config['button_size'] ?? $config['size'] ?? 'medium');
                $ls = $linkSizes[$sizeKey] ?? $linkSizes['medium'];
                $alignClass = match($linkAlignment) {
                    'center' => 'text-center',
                    'right' => 'text-right',
                    default => 'text-left',
                };
                $linkEffect = $config['button_effect'] ?? 'none';
                $linkHasEffect = $linkStyle === 'button' && $linkEffect !== 'none' && isset($effectGradients[$linkEffect]);
                $linkEffectId = 'lnk-' . md5($linkEffect);
                $linkRadius = (int) ($config['corner_radius'] ?? 8);
                $linkIcon = $config['button_icon'] ?? 'none';
                $linkIcons = [
                    'heart' => '<svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
                    'hand' => '<svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M23 12.22V15c0 4.97-4.03 9-9 9H9.17c-1.59 0-3.11-.63-4.24-1.76L0 17.5l1.5-1.5c.47-.47 1.08-.73 1.77-.73.46 0 .9.12 1.28.35L7 17.34V4.5C7 3.67 7.67 3 8.5 3S10 3.67 10 4.5v4h1V3.5C11 2.67 11.67 2 12.5 2S14 2.67 14 3.5V8.5h1V4c0-.83.67-1.5 1.5-1.5S18 3.17 18 4v5.5h1V6c0-.83.67-1.5 1.5-1.5S22 5.17 22 6v6.22z"/></svg>',
                    'star' => '<svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
                    'gift' => '<svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 12 7.01l3.38 4.99L17 10.83 14.92 8H20v6z"/></svg>',
                    'plus' => '<svg class="size-4" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
                ];
                $linkIconSvg = $linkIcon !== 'none' ? ($linkIcons[$linkIcon] ?? '') : '';
            @endphp
            @if($linkHasEffect)
                <style>
                    @keyframes ihsan-gradient-{{ $linkEffectId }} {
                        0%   { background-position: 0% 50%; }
                        50%  { background-position: 100% 50%; }
                        100% { background-position: 0% 50%; }
                    }
                    .ihsan-effect-{{ $linkEffectId }} {
                        background: {{ $effectGradients[$linkEffect] }};
                        background-size: 300% 300%;
                        animation: ihsan-gradient-{{ $linkEffectId }} 4s ease infinite;
                        box-shadow: {{ $effectShadows[$linkEffect] }};
                        transition: transform .2s ease, box-shadow .2s ease;
                    }
                </style>
            @endif
            <div class="flex min-h-[120px] items-center justify-center w-full">
                <div class="{{ $alignClass }} w-full">
                    @if($linkStyle === 'button')
                        <span class="ihsan-effect-{{ $linkHasEffect ? $linkEffectId : '' }} inline-flex items-center justify-center gap-2 font-semibold text-white shadow-sm transition-all"
                              style="{{ $linkHasEffect ? '' : 'background:' . $linkColor . ';' }}padding:{{ $ls['padding'] }};font-size:{{ $ls['font'] }};border-radius:{{ $linkRadius }}px;min-width:120px;cursor:pointer;">
                            {!! $linkIconSvg !!}
                            {{ $linkText }}
                        </span>
                    @else
                        <span class="font-medium underline underline-offset-2"
                              style="color:{{ $linkColor }};font-size:{{ $ls['font'] }};">
                            {{ $linkText }}
                        </span>
                    @endif
                </div>
            </div>

        @elseif($type === 'popup')
            @php
                $popupTitle = $config['title'] ?? 'Support Our Campaign Today';
                $popupMessage = $config['message'] ?? '';
                $popupButton = $config['button_text'] ?? 'Donate Now';
                $popupLayout = $config['layout'] ?? 'simple';
                $imageUrl = $config['image_url'] ?? null;
                $popupColor = $config['color'] ?? 'campaign';
                $colors = ['campaign' => '#16a34a', 'blue' => '#2563eb', 'teal' => '#0d9488', 'green' => '#16a34a', 'orange' => '#ea580c', 'red' => '#dc2626', 'purple' => '#9333ea', 'dark' => '#1e293b'];
                $accentColor = $colors[$popupColor] ?? '#16a34a';
                $popupEffect = $config['button_effect'] ?? 'none';
                $popupHasEffect = $popupEffect !== 'none' && isset($effectGradients[$popupEffect]);
                $popupEffectId = 'pop-' . md5($popupEffect);
            @endphp
            @if($popupHasEffect)
                <style>
                    @keyframes ihsan-gradient-{{ $popupEffectId }} {
                        0%   { background-position: 0% 50%; }
                        50%  { background-position: 100% 50%; }
                        100% { background-position: 0% 50%; }
                    }
                    .ihsan-effect-{{ $popupEffectId }} {
                        background: {{ $effectGradients[$popupEffect] }} !important;
                        background-size: 300% 300% !important;
                        animation: ihsan-gradient-{{ $popupEffectId }} 4s ease infinite;
                    }
                </style>
            @endif
            <div class="relative flex min-h-[300px] w-full items-center justify-center rounded-xl bg-zinc-50/80 p-4">
                <div class="relative w-full max-w-sm rounded-2xl bg-white shadow-xl ring-1 ring-zinc-200/60">
                    <button type="button" class="absolute -right-2 -top-2 z-10 flex h-9 w-9 items-center justify-center rounded-full border-2 border-zinc-200 bg-white text-zinc-500 shadow-md transition hover:bg-zinc-100 hover:text-zinc-900">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    @if($imageUrl && $popupLayout === 'full')
                        <div class="relative h-48 w-full overflow-hidden rounded-t-2xl">
                            <img src="{{ $imageUrl }}" class="h-full w-full object-cover" alt="">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                        </div>
                    @endif
                    <div class="p-6 text-center">
                        @if($imageUrl && $popupLayout === 'simple')
                            <div class="mb-8 overflow-hidden rounded-xl">
                                <img src="{{ $imageUrl }}" class="h-40 w-full object-cover" alt="">
                            </div>
                        @endif
                        <h3 class="text-lg font-bold tracking-tight text-zinc-900">{{ $popupTitle }}</h3>
                        @if($popupMessage)
                            <p class="mt-3 text-sm leading-relaxed text-zinc-500">{{ $popupMessage }}</p>
                        @endif
                        <div class="mt-6 flex items-center justify-center">
                            <button type="button" class="ihsan-effect-{{ $popupHasEffect ? $popupEffectId : '' }} min-w-[160px] rounded-lg px-6 py-3 text-sm font-semibold text-white shadow-md transition hover:shadow-lg hover:opacity-90" style="{{ $popupHasEffect ? '' : 'background-color:' . $accentColor }}">
                                {{ $popupButton }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        @elseif($type === 'form')
            <div
                x-data="{
                    selectedAmount: @js($defaultAmount),
                    frequency: @js($previewFrequency),
                    submitted: false,
                    monthlyAmounts: @js($defaultAmountsMonthly),
                    oneTimeAmounts: @js($defaultAmountsOneTime),
                    get amounts() {
                        return this.frequency === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts;
                    },
                }"
                class="relative mx-auto w-full max-w-[380px]"
            >
                <div class="min-h-[420px] rounded-[42px] bg-zinc-900 p-2.5 shadow-xl shadow-zinc-800/20 ring-1 ring-zinc-800">
                    <div class="relative min-h-[360px] overflow-hidden rounded-[30px] bg-white shadow-inner">
                        <div class="absolute left-1/2 top-3 z-10 flex h-7 w-24 -translate-x-1/2 items-center justify-center rounded-full bg-zinc-950">
                            <div class="size-2.5 rounded-full bg-zinc-900 ring-2 ring-zinc-800"></div>
                        </div>
                        <div x-show="submitted" x-cloak class="flex min-h-[360px] flex-col items-center px-6 pb-20 pt-24 text-center">
                            <div class="mb-6 flex size-16 items-center justify-center rounded-full bg-emerald-50">
                                <svg class="size-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-zinc-900">Thank You!</h3>
                            <p class="mt-2 text-sm text-zinc-500">Your donation has been submitted.</p>
                            <button
                                type="button"
                                x-on:click="submitted = false"
                                class="mt-8 rounded-xl bg-zinc-100 px-6 py-3 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-200"
                            >
                                Back to form
                            </button>
                        </div>
                        <div x-show="! submitted" class="min-h-[360px] space-y-4 px-4 pb-5 pt-12 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="flex size-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 5.25 6v5.25c0 4.2 2.86 8.1 6.75 9 3.89-.9 6.75-4.8 6.75-9V6L12 3.75Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 12.25 1.5 1.5 3-3.5" />
                                    </svg>
                                </span>
                                <h2 class="text-base font-bold tracking-normal text-zinc-950">Secure donation</h2>
                            </div>

                            @if($allowMonthly)
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    x-on:click="frequency = 'one_time'; selectedAmount = @js($defaultAmount)"
                                    x-bind:class="frequency === 'one_time' ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-zinc-200 bg-white text-zinc-500 hover:text-zinc-700'"
                                    class="rounded-lg border px-3 py-2.5 text-center font-semibold transition-all"
                                >
                                    Give once
                                </button>
                                <button
                                    type="button"
                                    x-on:click="frequency = 'monthly'; selectedAmount = @js($defaultAmount)"
                                    x-bind:class="frequency === 'monthly' ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-zinc-200 bg-white text-zinc-500 hover:text-zinc-700'"
                                    class="rounded-lg border px-3 py-2.5 text-center font-semibold transition-all"
                                >
                                    <span class="inline-flex items-center gap-1">
                                        <span class="text-red-400">&hearts;</span>
                                        Monthly
                                    </span>
                                </button>
                            </div>
                            @endif

                            @if(filled($title))
                                <p class="text-center text-xs font-semibold text-zinc-500">{{ $title }}</p>
                            @endif

                            @if($showSuggested)
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="amount in amounts" :key="amount">
                                    <button
                                        type="button"
                                        x-on:click="selectedAmount = amount"
                                        x-bind:class="Number(selectedAmount) === amount ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-zinc-200 bg-white text-zinc-600 hover:bg-zinc-50 hover:text-zinc-800'"
                                        class="rounded-lg border px-1 py-2.5 text-center text-sm font-bold transition-all active:scale-95"
                                    >
                                        <span x-text="'RM ' + Number(amount).toLocaleString('en-MY')"></span>
                                    </button>
                                </template>
                            </div>
                            @endif

                            @if($showAmountInput)
                            <div class="flex items-center rounded-xl border border-zinc-300 bg-white px-4 py-3 transition focus-within:border-teal-600 focus-within:ring-4 focus-within:ring-teal-100">
                                <span class="text-lg font-semibold text-zinc-700">RM</span>
                                <input
                                    x-model.number="selectedAmount"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-2 text-2xl font-bold text-zinc-950 outline-none placeholder:text-zinc-300 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                />
                                <span class="inline-flex items-center gap-1 text-sm font-semibold text-zinc-400">
                                    MYR
                                    <svg class="size-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                            @endif

                            @if($showDedication)
                            <label class="flex items-center gap-2 text-sm text-zinc-600">
                                <span class="size-5 rounded border border-zinc-300 bg-white"></span>
                                <span>Dedicate this donation</span>
                            </label>
                            @endif

                            @if($showComment)
                            <button type="button" class="text-sm font-medium text-zinc-600 underline underline-offset-2">
                                Add comment
                            </button>
                            @endif

                            @php
                                $formEffect = $config['button_effect'] ?? 'none';
                                $formHasEffect = $formEffect !== 'none' && isset($effectGradients[$formEffect]);
                                $formEffectId = 'frm-' . md5($formEffect);
                            @endphp
                            @if($formHasEffect)
                                <style>
                                    @keyframes ihsan-gradient-{{ $formEffectId }} {
                                        0%   { background-position: 0% 50%; }
                                        50%  { background-position: 100% 50%; }
                                        100% { background-position: 0% 50%; }
                                    }
                                    .ihsan-effect-{{ $formEffectId }} {
                                        background: {{ $effectGradients[$formEffect] }};
                                        background-size: 300% 300%;
                                        animation: ihsan-gradient-{{ $formEffectId }} 4s ease infinite;
                                    }
                                </style>
                            @endif
                            <button
                                type="button"
                                x-on:click="submitted = true"
                                class="ihsan-effect-{{ $formHasEffect ? $formEffectId : '' }} w-full rounded-xl {{ $formHasEffect ? '' : 'bg-teal-600 hover:bg-teal-700 shadow-teal-100 hover:shadow-teal-100' }} px-6 py-3 text-sm font-bold text-white shadow-lg transition-all hover:shadow-xl active:scale-[0.98]"
                            >
                                {{ $config['submit_text'] ?? 'Donate and Support' }}
                            </button>
                        </div>
                    </div>
                    <div class="flex justify-center pb-1.5 pt-5">
                        <div class="h-1.5 w-28 rounded-full bg-zinc-700"></div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
