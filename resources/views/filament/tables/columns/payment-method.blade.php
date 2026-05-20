@php
    $icon = match ($brand) {
        'visa' => '<svg viewBox="0 0 48 32" width="28" height="18" fill="none"><rect width="48" height="32" rx="4" fill="#1A1F71"/><path d="M18.5 10.5h-4l-3 11h4l3-11ZM24.5 10.5l-2.5 11h-3.5l2.5-11h3.5ZM29.5 10.5l-2.5 11h-3l2.5-11h3ZM36.5 10.5l-3 11h-3.5l3-11h3.5Z" fill="white"/><path d="M36.5 10.5l1.5 3.5 1-5.5" fill="white"/><path d="M36.5 10.5l1.5 3.5 1-5.5" fill="white" opacity="0.7"/><path fill="white" d="M39 21.5h3.5l-.5-2"/><path fill="white" d="M42 19.5h-3l-.5-2" opacity="0.7"/></svg>',
        'mastercard' => '<svg viewBox="0 0 48 32" width="28" height="18" fill="none"><rect width="48" height="32" rx="4" fill="#EDEFF1"/><circle cx="18" cy="16" r="8" fill="#EB001B"/><circle cx="30" cy="16" r="8" fill="#F79E1B" opacity="0.8"/></svg>',
        'amex' => '<svg viewBox="0 0 48 32" width="28" height="18" fill="none"><rect width="48" height="32" rx="4" fill="#2E77BC"/><path d="M6 12h4l2 3 2-3h4v8h-3v-5l-3 5-3-5v5H6v-8ZM22 12h4l3 4 3-4h4v8h-3v-5l-3 5-3-5v5h-4v-8ZM45 20h-8v-8h8v2h-5v1h5v2h-5v1h5v2Z" fill="white"/></svg>',
        'discover' => '<svg viewBox="0 0 48 32" width="28" height="18" fill="none"><rect width="48" height="32" rx="4" fill="#EDEFF1"/><rect x="2" y="2" width="44" height="28" rx="3" fill="#231F20"/><circle cx="22" cy="16" r="7" fill="#F5821F"/><path d="M35 14c-1 0-2 1-2 2s1 2 2 2 1.5-0.5 1.5-2-0.5-2-1.5-2Z" fill="#231F20"/></svg>',
        default => '<svg viewBox="0 0 48 32" width="28" height="18" fill="none"><rect width="48" height="32" rx="4" fill="#EDEFF1"/><rect x="8" y="10" width="32" height="12" rx="2" fill="#CBD5E1"/></svg>',
    };
@endphp

<div class="flex items-center gap-2">
    {!! $icon !!}
    <span class="text-sm text-gray-600 tracking-wider">•••• {{ $last4 }}</span>
</div>
