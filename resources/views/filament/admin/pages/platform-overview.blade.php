<x-filament-panels::page>
    <div class="ihsan-admin-page">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-admin.metric-card icon="heroicon-o-banknotes" label="Total donations" :value="'MYR '.$totalDonationsVolume" :note="$totalDonationsCount.' transactions'" />
            <x-admin.metric-card icon="heroicon-o-receipt-percent" label="Processing fees" :value="'MYR '.$totalProcessingFees" note="Transferred to platform" />
            <x-admin.metric-card icon="heroicon-o-arrow-path" label="Active subscriptions" :value="$activeSubscriptions" note="Across all organizations" />
            <x-admin.metric-card icon="heroicon-o-users" label="Total donors" :value="$totalDonors" note="Registered on platform" />
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">
                    Organizations
                </x-slot>
                <x-slot name="headerEnd">
                    <x-filament::badge color="gray">{{ $totalOrganizations }} total</x-filament::badge>
                </x-slot>

                <div class="grid grid-cols-3 gap-3">
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold text-amber-700 dark:text-amber-300">{{ $pendingOrganizations }}</div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">Pending</div>
                    </div>
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold text-emerald-700 dark:text-emerald-300">{{ $activeOrganizations }}</div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">Active</div>
                    </div>
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold text-red-700 dark:text-red-300">{{ $suspendedOrganizations }}</div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">Suspended</div>
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">
                    Recent organizations
                </x-slot>

                <div class="ihsan-admin-list">
                    @forelse ($recentOrganizations as $org)
                        <div class="ihsan-admin-list-row">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-ihsan-ink dark:text-white">{{ $org['name'] }}</div>
                                <div class="truncate text-sm text-ihsan-muted dark:text-stone-400">{{ $org['email'] }} · {{ $org['created_at'] }}</div>
                            </div>
                            <x-filament::badge :color="match($org['status']) { 'pending' => 'warning', 'active' => 'success', 'suspended' => 'danger', default => 'gray' }">
                                {{ ucfirst($org['status']) }}
                            </x-filament::badge>
                        </div>
                    @empty
                        <div class="py-3 text-sm text-ihsan-muted dark:text-stone-400">No organizations yet.</div>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            <x-slot name="heading">
                Recent donations
            </x-slot>

            <div class="ihsan-admin-list">
                @forelse ($recentDonations as $donation)
                    <div class="ihsan-admin-list-row">
                        <div class="min-w-0">
                            <div class="truncate text-sm font-medium text-ihsan-ink dark:text-white">{{ $donation['organization'] }}</div>
                            <div class="truncate text-sm text-ihsan-muted dark:text-stone-400">{{ $donation['campaign'] }}</div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="text-sm font-semibold text-ihsan-ink dark:text-white">{{ $donation['amount'] }}</span>
                                @if ($donation['original_amount'])
                                    <div class="text-xs text-ihsan-muted dark:text-stone-400">{{ $donation['original_amount'] }}</div>
                                @endif
                            </div>
                            <x-filament::badge :color="match($donation['status']) { 'succeeded' => 'success', 'pending' => 'warning', 'failed' => 'danger', 'refunded' => 'gray', default => 'gray' }">
                                {{ ucfirst($donation['status']) }}
                            </x-filament::badge>
                        </div>
                    </div>
                @empty
                    <div class="py-3 text-sm text-ihsan-muted dark:text-stone-400">No donations yet.</div>
                @endforelse
            </div>
        </x-filament::section>

        <div class="grid gap-6 xl:grid-cols-2">
            @livewire(\App\Filament\Widgets\DonationTrendChart::class)
            @livewire(\App\Filament\Widgets\ProcessingFeeTrendChart::class)
            @livewire(\App\Filament\Widgets\TopOrganizationsChart::class)
            @livewire(\App\Filament\Widgets\PaymentMethodChart::class)
        </div>
    </div>
</x-filament-panels::page>
