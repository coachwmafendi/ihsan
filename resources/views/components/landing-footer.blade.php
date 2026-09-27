{{-- The footer was written out once per page, in two colour schemes, so a link
     added to it reached only the page it was typed into.

     variant="auto" follows the theme toggle and is what the landing uses. The
     case study and the policy pages are light whatever the toggle says, so
     they ask for "light" and stay put. --}}
@props(['variant' => 'dark'])

@php
    $isLight = $variant === 'light';
    $isAuto = $variant === 'auto';

    $shell = match (true) {
        $isLight => 'border-slate-200 bg-teal-50/40',
        $isAuto => 'border-slate-200 dark:border-white/5',
        default => 'border-white/5',
    };

    $text = match (true) {
        $isLight => 'text-slate-400',
        $isAuto => 'text-slate-500 dark:text-slate-600',
        default => 'text-slate-600',
    };

    $link = match (true) {
        $isLight => 'hover:text-slate-600',
        $isAuto => 'hover:text-slate-900 dark:hover:text-slate-400',
        default => 'hover:text-slate-400',
    };

    $pill = match (true) {
        $isLight => 'text-slate-400 hover:text-slate-600 border-slate-200',
        $isAuto => 'text-slate-500 hover:text-slate-900 border-slate-200 dark:text-slate-600 dark:hover:text-slate-400 dark:border-white/10',
        default => 'text-slate-600 hover:text-slate-400 border-white/10',
    };
@endphp

<footer data-landing-footer class="border-t {{ $shell }} py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm {{ $text }}">
        <span>@lang('footer.copyright')</span>
        <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
            <a href="{{ route('legal.privacy') }}" class="{{ $link }} transition-colors">@lang('footer.privacy')</a>
            <a href="{{ route('legal.terms') }}" class="{{ $link }} transition-colors">@lang('footer.terms')</a>
            <a href="mailto:@lang('footer.email')" class="{{ $link }} transition-colors">@lang('footer.email')</a>
            <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ms' ? 'en' : 'ms']) }}" class="text-xs font-medium {{ $pill }} transition-colors border rounded-full px-3 py-1">
                @lang('nav.switch_language')
            </a>
        </div>
    </div>
</footer>
