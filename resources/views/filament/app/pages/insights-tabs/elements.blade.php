<div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Elements</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Embed buttons, forms, and popups</p>
        </div>
    </div>

    @if (count($elementsList) > 0)
        <div class="mt-8 divide-y divide-gray-100 dark:divide-gray-800">
            @foreach ($elementsList as $element)
                <div class="flex items-center justify-between py-3 text-sm" wire:key="elem-{{ $element['name'] }}">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-950 dark:text-white">
                            {{ $element['name'] }}
                            @if ($element['isActive'])
                                <span class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:bg-green-800/30 dark:text-green-400">Active</span>
                            @else
                                <span class="ml-2 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-800 dark:text-gray-400">Inactive</span>
                            @endif
                        </div>
                        <div class="text-gray-500 dark:text-gray-400">{{ $element['type'] }} &middot; {{ $element['campaign'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
        <p class="mt-4 text-sm text-gray-400">No donation attribution data yet for individual elements.</p>
    @else
        <div class="mt-8 flex items-center justify-center rounded-lg border border-dashed border-gray-300 p-12 dark:border-gray-700">
            <p class="text-sm text-gray-400">No elements created yet. Create embed buttons, forms, or popups from the Elements section.</p>
        </div>
    @endif
</div>
