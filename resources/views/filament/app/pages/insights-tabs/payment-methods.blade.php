<div>
    <x-ui.section-header
        title="Payment Methods"
        subtitle="Card brand and payment type distribution"
    />

    <div class="mt-8 grid gap-8 md:grid-cols-2">
        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Card Brands</h3>
            <div class="mt-4 space-y-4">
                @forelse ($paymentBrandBreakdown as $brand)
                    <div wire:key="brand-{{ $brand['brand'] }}">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">{{ $brand['brand'] }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $brand['count'] }} ({{ $brand['percentage'] }}%)</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ $brand['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No payment method data yet</p>
                @endforelse
            </div>
        </div>

        <div>
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Payment Types</h3>
            <div class="mt-4 space-y-4">
                @forelse ($paymentTypeBreakdown as $type)
                    <div wire:key="ptype-{{ $type['type'] }}">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">{{ $type['type'] }}</span>
                            <span class="font-medium text-gray-950 dark:text-white">{{ $type['total'] }} ({{ $type['percentage'] }}%)</span>
                        </div>
                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-full rounded-full bg-primary-500" style="width: {{ $type['percentage'] }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No payment type data yet</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
