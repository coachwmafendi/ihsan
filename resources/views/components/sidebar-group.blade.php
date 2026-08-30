{{-- resources/views/components/sidebar-group.blade.php --}}
@props(['label'])

<div class="px-3 py-2 transition-[padding] duration-300 ease-in-out motion-reduce:transition-none"
     :class="$store.sidebar.collapsed ? 'px-2' : 'px-3'">
    <div
        class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider whitespace-nowrap overflow-hidden"
        x-show="! $store.sidebar.collapsed"
        x-transition:enter="transition-opacity ease-out duration-200 delay-150 motion-reduce:transition-none"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-100 motion-reduce:transition-none"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        x-cloak
    >
        {!! $label !!}
    </div>
    <div class="mt-1 space-y-0.5" :class="$store.sidebar.collapsed ? 'mt-0' : 'mt-1'">
        {{ $slot }}
    </div>
</div>
