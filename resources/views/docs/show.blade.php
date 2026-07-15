@php
$segments = explode('/', $path ?? 'index');
$categorySlug = $segments[0] ?? null;
$pageSlug = $segments[1] ?? null;
$category = $categorySlug ? collect(config('docs.nav'))->firstWhere('slug', $categorySlug) : null;
$page = $category && $pageSlug ? collect($category['children'] ?? [])->firstWhere('slug', $pageSlug) : null;
@endphp

<x-layouts::docs :title="$title">
    @if ($category)
        <nav aria-label="Breadcrumb" class="mb-4 text-sm text-slate-500">
            <ol class="flex flex-wrap items-center gap-2">
                <li><a href="{{ route('docs.show') }}" class="hover:text-slate-900 hover:underline">Docs</a></li>
                <li class="text-slate-300">/</li>
                <li><a href="{{ route('docs.show', ['path' => $category['slug']]) }}" class="hover:text-slate-900 hover:underline">{{ $category['label'] }}</a></li>
                @if ($page)
                    <li class="text-slate-300">/</li>
                    <li class="text-slate-700" aria-current="page">{{ $page['label'] }}</li>
                @endif
            </ol>
        </nav>
    @endif

    <article class="prose prose-slate max-w-none">
        {!! $html !!}
    </article>
</x-layouts::docs>
