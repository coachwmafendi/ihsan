@props([
    'icon',
    'theme',
])

@php
    $label = __("filament-panels::layout.actions.theme_switcher.{$theme}.label");
@endphp

<button
    aria-label="{{ $label }}"
    type="button"
    x-on:click="(theme = @js($theme)) && close()"
    x-tooltip="{
        content: @js($label),
        theme: $store.theme,
    }"
    x-bind:aria-pressed="theme === @js($theme)"
    x-bind:class="theme === @js($theme) ? 'bg-primary/15 ring-1 ring-primary/40 text-primary shadow-sm' : 'text-muted-foreground hover:bg-muted'"
    class="fi-theme-switcher-btn rounded-lg p-1.5 transition duration-150"
>
    {{
        \Filament\Support\generate_icon_html($icon, alias: match ($theme) {
            'light' => \Filament\View\PanelsIconAlias::THEME_SWITCHER_LIGHT_BUTTON,
            'dark' => \Filament\View\PanelsIconAlias::THEME_SWITCHER_DARK_BUTTON,
            'system' => \Filament\View\PanelsIconAlias::THEME_SWITCHER_SYSTEM_BUTTON,
        })
    }}
</button>
