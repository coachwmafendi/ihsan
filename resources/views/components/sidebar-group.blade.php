{{-- resources/views/components/sidebar-group.blade.php --}}
@props(['label'])

<div class="px-3 py-2">
    <div class="px-3 py-1.5 text-xs font-semibold text-slate-400 uppercase tracking-wider">
        {{ $label }}
    </div>
    <div class="mt-1 space-y-0.5">
        {{ $slot }}
    </div>
</div>
