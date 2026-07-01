{{-- resources/views/components/sidebar-group.blade.php --}}
@props(['label'])

<div class="px-3 py-2"
     :class="$store.sidebar.collapsed ? 'px-2' : 'px-3'">
    <div
        class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider whitespace-nowrap overflow-hidden"
        x-show="! $store.sidebar.collapsed"
        x-cloak
    >
        {!! $label !!}
    </div>
    <div class="mt-1 space-y-0.5" :class="$store.sidebar.collapsed ? 'mt-0' : 'mt-1'">
        {{ $slot }}
    </div>
</div>
