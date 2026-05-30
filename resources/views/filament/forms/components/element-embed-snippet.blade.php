@php
    $type = $liveType ?? ($element?->type?->value ?? null);
    $baseUrl = config('app.url');
    $widgetSrc = $baseUrl.'/e/widget.js';
@endphp

@if ($element && $type)
    <div class="col-span-full space-y-4">
        <p class="text-xs font-semibold uppercase tracking-wide text-zinc-500">
            Embed kod — {{ ucwords(str_replace('_', ' ', $type)) }}
        </p>

        @php
            $embedCode = '<script src="'.$widgetSrc.'" data-token="'.$element->token.'"></script>';
        @endphp

        @if ($embedCode)
            <div
                x-data="{ code: @js($embedCode), copied: false }"
                class="overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50"
            >
                <div class="flex items-center justify-between gap-3 border-b border-zinc-200 px-4 py-2.5">
                    <span class="text-xs font-medium text-zinc-500">Salin kod ini ke halaman web anda</span>
                    <button
                        type="button"
                        x-on:click="navigator.clipboard.writeText(code).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                        class="rounded-md bg-white px-3 py-1.5 text-xs font-semibold text-zinc-700 shadow-sm ring-1 ring-zinc-200 hover:bg-zinc-100"
                    >
                        <span x-show="!copied">Salin</span>
                        <span x-show="copied" x-cloak class="text-emerald-600">Disalin!</span>
                    </button>
                </div>
                <pre class="overflow-x-auto p-4 text-xs leading-relaxed text-zinc-700"><code x-text="code"></code></pre>
            </div>
        @endif
    </div>
@endif
