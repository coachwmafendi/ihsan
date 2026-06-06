@php
$org = auth()->user()->organization;
$portalUrl = $org ? route('donorportal.dashboard', $org) : null;
@endphp

@if ($portalUrl)
    <div class="flex items-center gap-2">
        <div class="flex-1 min-w-0 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 truncate">
            {{ $portalUrl }}
        </div>
        <button
            type="button"
            x-data="{ copied: false }"
            @click="navigator.clipboard.writeText('{{ $portalUrl }}'); copied = true; setTimeout(() => copied = false, 2000); $dispatch('notify', { message: 'Copied!' })"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            title="Copy link"
        >
            <x-heroicon-o-clipboard-document x-show="!copied" class="size-4" />
            <x-heroicon-o-check x-show="copied" x-cloak class="size-4 text-green-600" />
            <span x-text="copied ? 'Link sudah dicopy' : 'Copy'"></span>
        </button>
        <a
            href="{{ $portalUrl }}"
            target="_blank"
            rel="noopener"
            class="shrink-0 inline-flex items-center gap-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            title="Open in new tab"
        >
            <x-heroicon-o-arrow-top-right-on-square class="size-4" />
            Open
        </a>
    </div>
@endif
