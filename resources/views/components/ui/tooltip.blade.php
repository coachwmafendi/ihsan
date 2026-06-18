{{-- resources/views/components/ui/tooltip.blade.php --}}
@props([
    'text' => null,
    'position' => 'top',
    'align' => 'center',
    'delay' => 75,
    'maxWidth' => 'max-w-xs',
    'disabled' => false,
])

@php
    $slotHtml = trim($slot->toHtml());

    $isInteractive = preg_match('/^<(button|a|input|select|textarea|flux:button|flux:button-or-link)\b/i', $slotHtml) === 1;

    $tipHtml = ($tip ?? null) ? $tip->toHtml() : null;
    $hasContent = filled($text) || filled($tipHtml);
    $tooltipText = trim(strip_tags($text ?? ($tipHtml ? $tipHtml : '')));
@endphp

<span
    {{ $attributes->merge(['class' => 'inline-block align-middle']) }}
    x-data="uiTooltip({ text: @js($tooltipText), position: @js($position), align: @js($align), delay: @js((int) $delay), disabled: @js((bool) $disabled) })"
    :aria-describedby="open ? tipId : null"
    @if (! $isInteractive && ! $disabled && $hasContent)
        tabindex="0"
        role="img"
        aria-label="{{ $tooltipText }}"
    @endif
    @mouseenter="show()"
    @mouseleave="hide()"
    @focusin="show(0)"
    @focusout="hide()"
    @keydown.escape.window="hide()"
    x-effect="if (open) { triggerEl?.setAttribute('aria-describedby', tipId); } else { triggerEl?.removeAttribute('aria-describedby'); }"
>
    {{ $slot }}

    @if (! $disabled && $hasContent)
        <template x-teleport="body">
            <div
                x-show="open"
                x-cloak
                x-ref="tooltip"
                :id="tipId"
                role="tooltip"
                class="fixed {{ $maxWidth }} z-50 whitespace-normal rounded-md bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-white shadow-lg"
                :style="style"
                x-transition:enter="transition ease-out duration-75"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
            >
                @if ($tipHtml)
                    {!! $tipHtml !!}
                @else
                    {{ $text }}
                @endif
            </div>
        </template>
    @endif
</span>
