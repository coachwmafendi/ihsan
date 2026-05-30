@php
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
</div>
