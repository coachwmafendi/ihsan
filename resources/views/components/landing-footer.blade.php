{{-- The footer was written out once per page, in two colour schemes, so a link
     added to it reached only the page it was typed into. --}}
@props(['variant' => 'dark'])

@php
    $isLight = $variant === 'light';
    $border = $isLight ? 'border-slate-200 bg-teal-50/40' : 'border-white/5';
    $text = $isLight ? 'text-slate-400' : 'text-slate-600';
    $link = $isLight ? 'hover:text-slate-600' : 'hover:text-slate-400';
    $pill = $isLight ? 'text-slate-400 hover:text-slate-600 border-slate-200' : 'text-slate-600 hover:text-slate-400 border-white/10';
@endphp

<footer data-landing-footer class="border-t {{ $border }} py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-sm {{ $text }}">
        <span>@lang('footer.copyright')</span>
        <div class="flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
            <a href="mailto:@lang('footer.email')" class="{{ $link }} transition-colors">@lang('footer.email')</a>
            <a href="{{ route('language.switch', ['locale' => app()->getLocale() === 'ms' ? 'en' : 'ms']) }}" class="text-xs font-medium {{ $pill }} transition-colors border rounded-full px-3 py-1">
                @lang('nav.switch_language')
            </a>
        </div>
    </div>
</footer>
