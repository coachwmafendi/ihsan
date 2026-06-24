@php
    use App\Support\EmbedWidget;

    $type = $liveType ?? ($element?->type?->value ?? null);
    $baseUrl = url('/');
    $widgetSrc = EmbedWidget::scriptUrl();
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

        $widgetSrc = EmbedWidget::scriptUrl();

        if ($cleanType === 'button') {
            $staticButton = \App\Support\EmbedWidget::staticButtonHtml($element);
            $embedCode = $staticButton."\n".'<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>';
            $hasIframeFallback = false;
        } else {
            $embedCode = '<script src="'.$widgetSrc.'" data-token="'.$token.'" data-api-base="'.$baseUrl.'"></script>';
            $hasIframeFallback = in_array($cleanType, ['link'], true);
        }

        $parentScript = '<script src="'.$baseUrl.'/e/parent.js"></script>';

        $iframeCode = $hasIframeFallback
            ? '<iframe src="'.$baseUrl.'/e/button/'.$token.'" width="100%" height="70" frameborder="0" scrolling="no" style="border:0;overflow:hidden;"></iframe>' . "\n" . $parentScript
            : null;
    @endphp

    @if ($embedCode)
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
            <x-ui.tooltip text="Click to copy">
                <pre class="overflow-x-auto cursor-pointer select-all p-4 text-xs leading-relaxed text-zinc-700 transition hover:bg-zinc-100/50"
                     x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                ><code x-text="code" class="pointer-events-none"></code></pre>
            </x-ui.tooltip>
        </div>

        @if ($iframeCode)
        <div
            x-data="{ code: @js($iframeCode), copied: false }"
            class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50"
        >
            <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-2.5">
                <span class="text-xs font-medium text-zinc-500">WordPress / visual editor (iframe)</span>
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                    class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100"
                >
                    <span x-show="!copied">Copy</span>
                    <span x-show="copied" x-cloak class="text-emerald-600">Copied!</span>
                </button>
            </div>
            <x-ui.tooltip text="Click to copy">
                <pre class="overflow-x-auto cursor-pointer select-all p-4 text-xs leading-relaxed text-zinc-700 transition hover:bg-zinc-100/50"
                     x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                ><code x-text="code" class="pointer-events-none"></code></pre>
            </x-ui.tooltip>
        </div>
        @endif
    </div>
    @endif
@endif
