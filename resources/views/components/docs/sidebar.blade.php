@props(['path' => 'index'])

@php
$segments = explode('/', $path);
$currentCategory = $segments[0] ?? 'index';
$currentPage = $segments[1] ?? 'index';
@endphp

<nav class="space-y-6" aria-label="Documentation">
    <a href="{{ route('docs.show') }}"
       class="block text-sm font-semibold {{ $path === 'index' ? 'text-teal-600' : 'text-slate-700 hover:text-slate-900' }}"
       aria-current="{{ $path === 'index' ? 'page' : 'false' }}">
        @lang('docs.nav.documentation_home')
    </a>

    @foreach (config('docs.nav') as $category)
        @php
            $categorySlug = $category['slug'] ?? '';
            $categoryLabel = __('docs.nav.'.$categorySlug.'.label');
            $categoryActive = $currentCategory === $categorySlug;
            $children = $category['children'] ?? [];
        @endphp
        <div>
            <p class="px-2 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">{{ $categoryLabel }}</p>
            <ul class="space-y-1">
                @foreach ($children as $child)
                    @php
                        $childSlug = $child['slug'] ?? '';
                        $childLabel = __('docs.nav.'.$categorySlug.'.children.'.$childSlug);
                        $isIndex = $childSlug === 'index';
                        $childPath = $isIndex ? $categorySlug : $categorySlug.'/'.$childSlug;
                        $isActive = $isIndex
                            ? ($path === $categorySlug || ($categoryActive && $currentPage === 'index'))
                            : $path === $childPath;
                    @endphp
                    <li>
                        <a href="{{ route('docs.show', ['path' => $childPath]) }}"
                           class="block px-2 py-1.5 rounded-md text-sm {{ $isActive ? 'bg-teal-50 text-teal-700 font-medium' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900' }}"
                           aria-current="{{ $isActive ? 'page' : 'false' }}">
                            {{ $childLabel }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
