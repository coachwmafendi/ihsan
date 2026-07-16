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
    wire:ignore.self
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
    x-effect="if (open) { currentTriggerEl()?.setAttribute('aria-describedby', tipId); } else { currentTriggerEl()?.removeAttribute('aria-describedby'); }"
>
    {{ $slot }}

    @if (! $disabled && $hasContent)
        <template x-teleport="body" wire:ignore>
            <div
                x-show="open"
                x-cloak
                x-ref="tooltip"
                :id="tipId"
                role="tooltip"
                class="fixed {{ $maxWidth }} z-50 whitespace-normal rounded-xl bg-white px-3 py-2 text-xs font-medium leading-relaxed text-slate-700 shadow-[0_10px_30px_rgba(15,23,42,0.15)] ring-1 ring-slate-900/10"
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
                    {{ $tooltipText }}
                @endif
            </div>
        </template>
    @endif
</span>
