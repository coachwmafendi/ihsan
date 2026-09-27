{{-- The class it flips is set before first paint by the script in the landing
     layout's head, so this only has to keep up with it and remember the
     choice. --}}
<button
    type="button"
    x-data="{
        dark: document.documentElement.classList.contains('dark'),
        toggle() {
            this.dark = ! this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },
    }"
    x-on:click="toggle()"
    x-bind:aria-label="dark ? '{{ __('nav.theme_light') }}' : '{{ __('nav.theme_dark') }}'"
    class="inline-flex items-center justify-center rounded-full border border-slate-200 dark:border-white/10 p-1.5 text-slate-500 transition-colors hover:text-slate-900 dark:hover:text-slate-300"
>
    <x-heroicon-o-sun class="size-4" x-show="dark" x-cloak />
    <x-heroicon-o-moon class="size-4" x-show="! dark" x-cloak />
</button>
