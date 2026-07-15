@php
$segments = explode('/', $path ?? 'index');
$categorySlug = $segments[0] ?? null;
$pageSlug = $segments[1] ?? null;
$category = $categorySlug ? collect(config('docs.nav'))->firstWhere('slug', $categorySlug) : null;
$page = $category && $pageSlug ? collect($category['children'] ?? [])->firstWhere('slug', $pageSlug) : null;
@endphp

<x-layouts::docs :title="$title">
    @if ($path === 'index')
        @php
            $landing = config('docs.landing', []);
        @endphp
        <header class="mb-8">
            <img src="{{ $landing['hero_image'] ?? '' }}"
                 alt="{{ __('docs.landing.hero_alt') }}"
                 class="w-full h-48 sm:h-64 object-cover rounded-2xl border border-slate-200 shadow-sm mb-6">
        </header>
    @elseif ($category)
        <nav aria-label="Breadcrumb" class="mb-4 text-sm text-slate-500">
            <ol class="flex flex-wrap items-center gap-2">
                @php
                    $categorySlug = $category['slug'] ?? '';
                    $pageSlug = $page['slug'] ?? '';
                @endphp
                <li><a href="{{ route('docs.show') }}" class="hover:text-slate-900 hover:underline">@lang('docs.nav.docs')</a></li>
                <li class="text-slate-300">/</li>
                <li><a href="{{ route('docs.show', ['path' => $categorySlug]) }}" class="hover:text-slate-900 hover:underline">{{ __('docs.nav.'.$categorySlug.'.label') }}</a></li>
                @if ($page)
                    <li class="text-slate-300">/</li>
                    <li class="text-slate-700" aria-current="page">{{ __('docs.nav.'.$categorySlug.'.children.'.$pageSlug) }}</li>
                @endif
            </ol>
        </nav>
    @endif

    <article class="prose prose-slate max-w-none">
        {!! $html !!}
    </article>

    @if ($path === 'index')
        @php
            $landingCards = config('docs.landing.cards', []);
        @endphp
        <section class="mt-12">
            <h2 class="text-xl font-semibold text-slate-900 mb-4">@lang('docs.nav.browse_by_topic')</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 not-prose">
                @foreach (config('docs.nav') as $category)
                    @php
                        $categorySlug = $category['slug'] ?? '';
                        $card = $landingCards[$categorySlug] ?? null;
                        $cardImage = $card['image'] ?? null;
                    @endphp
                    <a href="{{ route('docs.show', ['path' => $categorySlug]) }}"
                       class="group block bg-white rounded-xl border border-slate-200 overflow-hidden hover:shadow-md transition-shadow">
                        @if ($cardImage)
                            <img src="{{ $cardImage }}"
                                 alt="{{ __('docs.landing.cards.'.$categorySlug.'.title') }}"
                                 class="w-full h-32 object-cover border-b border-slate-200">
                        @endif
                        <div class="p-4">
                            <h3 class="font-semibold text-slate-900 group-hover:text-teal-700">{{ __('docs.landing.cards.'.$categorySlug.'.title') }}</h3>
                            <p class="mt-1 text-sm text-slate-600">{{ __('docs.landing.cards.'.$categorySlug.'.description') }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts::docs>
