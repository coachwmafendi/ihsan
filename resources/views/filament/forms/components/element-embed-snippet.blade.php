@php
    $type = $liveType ?? ($element?->type?->value ?? null);
    $baseUrl = config('app.url');
    $widgetSrc = $baseUrl.'/e/widget.js';
    $token = $element?->token;
@endphp

@if ($element && $type && $token)
    @php
        $cleanType = match ($type) {
            'floating_button' => 'floating_button',
            'qr_code' => 'qr_code',
            'link' => 'link',
            default => $type,
        };

        if (in_array($cleanType, ['button', 'floating_button', 'popup', 'link'], true)) {
            $embedCode = '<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'"></script>';
        } elseif ($cleanType === 'form') {
            $embedId = 'ihsan-'.e($token);
            $embedCode = '<div id="'.$embedId.'" style="width:100%;">'."\n"
                .'<iframe'."\n"
                .'  src="'.e($baseUrl.'/donate/'.$token.'?embed=1').'"'."\n"
                .'  width="100%"'."\n"
                .'  height="700"'."\n"
                .'  frameborder="0"'."\n"
                .'  allow="payment *"'."\n"
                .'  style="border:0;border-radius:16px;"'."\n"
                .'></iframe>'."\n"
                .'</div>'."\n"
                .'<style>@media(min-width:768px){#'.$embedId.'{max-width:50%;margin:0 auto}}</style>';
        } elseif ($cleanType === 'qr_code') {
            $embedCode = '<img src="'.e($baseUrl.'/donate/'.$token.'/qr').'" alt="QR Code" width="200" height="200">';
        } else {
            $embedCode = '<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'"></script>';
        }
    @endphp

    <div class="col-span-full space-y-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
            Embed code — {{ ucwords(str_replace('_', ' ', $type)) }}
        </p>

        <div
            x-data="{ code: @js($embedCode), copied: false }"
            class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50"
        >
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-2.5">
                <span class="text-xs font-medium text-zinc-500">Copy this code to your website</span>
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100"
                >
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak class="text-emerald-600">Copied!</span>
                </button>
            </div>
            <pre class="overflow-x-auto cursor-pointer select-all p-4 text-xs leading-relaxed text-zinc-700 transition hover:bg-zinc-100/50"
                 title="Click to copy"
                 x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
            ><code x-text="code" class="pointer-events-none"></code></pre>
        </div>
    </div>
@endif
