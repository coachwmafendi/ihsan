@props(['variant' => 'checkout'])

@php
    /** @var array{headline: string, detail: string} $guidance */
    $guidance = match ($variant) {
        'content' => [
            'headline' => 'Best size: <strong class="font-semibold text-slate-600">1200 &times; 900 px</strong> (4:3). Keep the subject centred.',
            'detail' => 'The campaign page shows this image about 544 &times; 448 px on desktop and full width on mobile, cropped to fill. '
                .'Upload 1200 &times; 900 px so it stays sharp on retina screens, and keep the file under 400 KB &mdash; this image is served as uploaded, without resizing.',
        ],
        'logo' => [
            'headline' => 'Best size: <strong class="font-semibold text-slate-600">240 &times; 80 px</strong>, transparent PNG or WebP.',
            'detail' => 'The campaign page scales the logo to 40 px tall and keeps its aspect ratio, so upload it at twice that height for retina screens. '
                .'Trim the empty space around the mark, keep the file under 100 KB, and avoid a white box behind it.',
        ],
        default => [
            'headline' => 'Best size: <strong class="font-semibold text-slate-600">1600 &times; 900 px</strong> (16:9). Keep the subject centred.',
            'detail' => 'The checkout modal crops this image to fill its frame, so the edges are trimmed differently on each screen: '
                .'about 520 &times; 192 px on mobile, up to 800 &times; 360 px on desktop, and 552 &times; 256 px in the card layout. '
                .'Upload 1600 &times; 900 px so every crop stays sharp on retina screens, keep the file under 300 KB, and avoid '
                .'text in the image &mdash; it is unreadable in the short mobile crop.',
        ],
    };
@endphp

<p {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-1 text-xs text-slate-500']) }}>
    <span>{!! $guidance['headline'] !!}</span>
    <x-ui.tooltip position="top" max-width="max-w-sm">
        <button type="button" class="inline-flex text-slate-400 hover:text-slate-600" aria-label="Image size guidance">
            <x-heroicon-o-information-circle class="size-4" />
        </button>
        <x-slot:tip>{!! $guidance['detail'] !!}</x-slot:tip>
    </x-ui.tooltip>
</p>
