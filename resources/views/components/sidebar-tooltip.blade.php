{{-- resources/views/components/sidebar-tooltip.blade.php --}}
@props([
    'text' => null,
    'position' => 'right',
    'align' => 'center',
    'delay' => 75,
])

@php
$tooltipText = trim(strip_tags($text ?? ''));
@endphp

<span
    class="block"
    x-data="uiTooltip({ text: @js($tooltipText), position: @js($position), align: @js($align), delay: @js((int) $delay), disabled: ! $store.sidebar.collapsed })"
    x-effect="disabled = ! $store.sidebar.collapsed"
    @mouseenter="show()"
    @mouseleave="hide()"
    @focusin="show(0)"
    @focusout="hide()"
    @keydown.escape.window="hide()"
    x-effect="disabled = ! $store.sidebar.collapsed; if (open) { triggerEl?.setAttribute('aria-describedby', tipId); } else { triggerEl?.removeAttribute('aria-describedby'); }"
>
    {{ $slot }}

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-ref="tooltip"
            :id="tipId"
            role="tooltip"
            class="fixed z-50 whitespace-nowrap rounded-md bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-white shadow-lg"
            :style="style"
            x-transition:enter="transition ease-out duration-75"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            {{ $text }}
        </div>
    </template>
</span>
