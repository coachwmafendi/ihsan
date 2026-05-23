@php
    $waUrl = $url ? 'https://wa.me/?text=' . urlencode($url) : null;
    $qrUrl = $url ? 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . urlencode($url) . '&bgcolor=ffffff&color=0f172a&qzone=1' : null;
    $waIcon = '<svg class="size-4 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>';
@endphp

<div class="col-span-full space-y-4">
    <div
        x-data="{ copied: false }"
        data-url="{{ $url ?? '' }}"
        class="flex overflow-hidden rounded-xl border border-zinc-300 bg-zinc-100 shadow-sm"
    >
        <code class="flex-1 truncate px-4 py-3 font-mono text-sm text-zinc-700">
            {{ $url ?? 'Tersedia selepas simpan element.' }}
        </code>
        @if ($url)
            <button
                type="button"
                x-on:click="navigator.clipboard.writeText($root.dataset.url).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                class="flex items-center gap-1.5 border-l border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50"
            >
                <span x-show="!copied">Salin URL</span>
                <span x-show="copied" x-cloak class="text-emerald-600">Disalin!</span>
            </button>
            <a
                href="{{ $url }}"
                target="_blank"
                rel="noopener"
                class="flex items-center border-l border-zinc-300 bg-white px-4 py-3 text-sm font-semibold text-zinc-700 transition hover:bg-zinc-50"
            >
                Buka →
            </a>
        @endif
    </div>

    @if ($url)
        <div class="flex items-start gap-6">
            <div class="shrink-0 text-center">
                <img
                    src="{{ $qrUrl }}"
                    width="140"
                    height="140"
                    loading="lazy"
                    class="rounded-lg border border-zinc-200"
                    alt="QR Code"
                >
                <p class="mt-1.5 text-xs text-zinc-400">Imbas untuk derma</p>
            </div>
            <div class="flex-1 space-y-3 pt-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-zinc-400">Kongsi</p>
                <a
                    href="{{ $waUrl }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center gap-2 rounded-lg bg-green-500 px-4 py-2 text-sm font-medium text-white transition hover:bg-green-600"
                >
                    {!! $waIcon !!} WhatsApp
                </a>
            </div>
        </div>
    @endif
</div>
