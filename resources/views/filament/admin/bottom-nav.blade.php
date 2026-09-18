@php
    use App\Filament\Pages\PlatformOverview;
    use App\Filament\Pages\Revenue;
    use App\Filament\Pages\Transactions;
    use App\Filament\Resources\Organizations\OrganizationResource;

    $items = [
        [
            'label' => 'Overview',
            'icon' => 'heroicon-o-globe-alt',
            'url' => PlatformOverview::getUrl(),
        ],
        [
            'label' => 'Transactions',
            'icon' => 'heroicon-o-credit-card',
            'url' => Transactions::getUrl(),
        ],
        [
            'label' => 'Organizations',
            'icon' => 'heroicon-o-rectangle-stack',
            'url' => OrganizationResource::getUrl(),
        ],
        [
            'label' => 'Revenue',
            'icon' => 'heroicon-o-banknotes',
            'url' => Revenue::getUrl(),
        ],
    ];

    $currentUrl = url()->current();
@endphp

{{-- Stands down while the sidebar is open rather than sitting on top of it. --}}
<nav
    class="ihsan-admin-bottom-nav"
    data-test="admin-bottom-nav"
    x-data
    x-show="! $store.sidebar.isOpen"
>
    @foreach ($items as $item)
        @php
            $isCurrent = str_starts_with($currentUrl, $item['url']);
        @endphp

        <a
            href="{{ $item['url'] }}"
            wire:navigate
            @class([
                'ihsan-admin-bottom-nav-item',
                'ihsan-admin-bottom-nav-item-active' => $isCurrent,
            ])
            @if ($isCurrent) aria-current="page" @endif
        >
            <x-dynamic-component :component="$item['icon']" class="size-6" />
            <span class="ihsan-admin-bottom-nav-label">{{ $item['label'] }}</span>
        </a>
    @endforeach

    {{-- The rest of the navigation already lives in the sidebar. --}}
    <button
        type="button"
        x-data
        @click="$store.sidebar.open()"
        class="ihsan-admin-bottom-nav-item"
    >
        <x-heroicon-o-bars-3 class="size-6" />
        <span class="ihsan-admin-bottom-nav-label">More</span>
    </button>
</nav>
