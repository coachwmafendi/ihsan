{{-- The shell both legal documents sit in, so they read the same and only
     their prose differs. Light body: these are long pages to read, unlike the
     dark landing they are linked from. --}}
@props(['title', 'updated'])

<x-layouts::landing>
    <x-slot:title>{{ $title }} — Ihsan</x-slot:title>
    <x-slot:bodyClass>bg-white text-slate-600</x-slot:bodyClass>

    <header class="border-b border-slate-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-slate-900 font-bold text-lg tracking-tight">
                <x-app-logo-icon class="h-7 w-auto" />
                <span>Ihsan</span>
            </a>
            <a href="{{ route('home') }}" class="text-sm text-slate-500 hover:text-slate-900 transition-colors">@lang('nav.back_home')</a>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
        <p class="mt-2 text-sm text-slate-500">Last updated {{ $updated }}</p>

        <div class="mt-10 text-base leading-relaxed
                    [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-slate-900 [&_h2]:mt-10 [&_h2]:mb-3
                    [&_p]:mb-4
                    [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:space-y-2 [&_ul]:mb-4
                    [&_strong]:text-slate-900
                    [&_a]:text-teal-700 [&_a]:underline [&_a:hover]:text-teal-900">
            {{ $slot }}
        </div>
    </main>

    <x-landing-footer variant="light" />
</x-layouts::landing>
