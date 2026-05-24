<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total donations</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalDonationsVolume }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $totalDonationsCount }} transactions</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Processing fees</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">MYR {{ $totalProcessingFees }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Transferred to platform</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Active subscriptions</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $activeSubscriptions }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Across all NGOs</div>
            </x-filament::section>

            <x-filament::section>
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total donors</div>
                <div class="mt-2 text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">{{ $totalDonors }}</div>
                <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">Registered on platform</div>
            </x-filament::section>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">
                    Organizations
                </x-slot>
                <x-slot name="headerEnd">
                    <x-filament::badge color="gray">{{ $totalOrganizations }} total</x-filament::badge>
                </x-slot>

                <div class="mt-2 grid grid-cols-3 gap-3">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-lg font-semibold text-amber-600 dark:text-amber-400">{{ $pendingOrganizations }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Pending</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-lg font-semibold text-green-600 dark:text-green-400">{{ $activeOrganizations }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Active</div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-lg font-semibold text-red-600 dark:text-red-400">{{ $suspendedOrganizations }}</div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Suspended</div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Recent organizations
                </x-slot>

                <div class="mt-2 divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse ($recentOrganizations as $org)
                        <div class="flex items-center justify-between gap-4 py-3">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $org['name'] }}</div>
                                <div class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $org['email'] }} · {{ $org['created_at'] }}</div>
                            </div>
                            <x-filament::badge :color="match($org['status']) { 'pending' => 'warning', 'active' => 'success', 'suspended' => 'danger', default => 'gray' }">
                                {{ ucfirst($org['status']) }}
                            </x-filament::badge>
                        </div>
                    @empty
                        <div class="py-3 text-sm text-gray-500 dark:text-gray-400">No organizations yet.</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                Recent donations
            </x-slot>

            <div class="mt-2 divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($recentDonations as $donation)
                    <div class="flex items-center justify-between gap-4 py-3">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $donation['organization'] }}</div>
                            <div class="truncate text-sm text-gray-500 dark:text-gray-400">{{ $donation['campaign'] }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $donation['amount'] }}</span>
                            <x-filament::badge :color="match($donation['status']) { 'succeeded' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'gray', default => 'gray' }">
                                {{ ucfirst($donation['status']) }}
                            </x-filament::badge>
                        </div>
                    </div>
                @empty
                    <div class="py-3 text-sm text-gray-500 dark:text-gray-400">No donations yet.</div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
