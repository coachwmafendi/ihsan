@php
    $type = $record?->type?->value;
    $token = $record?->token;
    $baseUrl = config('app.url');
    $widgetSrc = $baseUrl.'/e/widget.js';

    if (! $record || ! $type || ! $token) return;

    if (in_array($type, ['button', 'floating_button', 'popup', 'link', 'form'], true)) {
        $embedCode = '<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'"></script>';
    } elseif ($type === 'qr_code') {
        $embedCode = '<img src="'.$baseUrl.'/donate/'.$token.'/qr" alt="QR Code" width="200" height="200">';
    } else {
        $embedCode = '<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'"></script>';
    }
@endphp

<div class="flex items-center gap-1.5 max-w-md">
    <code class="flex-1 truncate text-[10px] leading-relaxed text-zinc-500 font-mono bg-zinc-100 rounded px-1.5 py-1 select-all"
        title="Copy embed code"
        onclick="event.stopPropagation();navigator.clipboard.writeText(this.textContent)"
    >{{ $embedCode }}</code>
    <button
        type="button"
        title="Copy embed code"
        onclick="event.stopPropagation();var c=this.previousElementSibling.textContent;navigator.clipboard.writeText(c)"
        class="shrink-0 rounded p-1 text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 transition"
    >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
    </button>
</div>
