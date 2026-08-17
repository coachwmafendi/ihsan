<x-filament-panels::page>
    <div class="ihsan-admin-page">
        {{-- Row 1: All-time platform totals --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <x-admin.metric-card icon="heroicon-o-banknotes" label="Total donations" :value="($totalDonationsHasApproximation ? '≈ ' : '').'MYR '.$totalDonationsVolume" :note="'All time · '.$totalDonationsCount.' transactions'" />
            <x-admin.metric-card icon="heroicon-o-receipt-percent" label="Processing fees" :value="'MYR '.$totalProcessingFees" note="All time · transferred to platform" />
            <x-admin.metric-card icon="heroicon-o-arrow-path" label="Active subscriptions" :value="$activeSubscriptions" note="Currently active" />
            <x-admin.metric-card icon="heroicon-o-users" label="Total donors" :value="$totalDonors" note="All time registered" />
        </div>

        {{-- Row 2: Monthly pulse --}}
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <x-admin.metric-card
                icon="heroicon-o-chart-bar"
                label="Estimated MRR"
                :value="($estimatedMrrHasApproximation ? '≈ ' : '').'MYR '.$estimatedMrr"
                note="Active subscriptions normalized monthly"
            />
            <x-admin.metric-card
                icon="heroicon-o-banknotes"
                label="Donations this month"
                :value="($donationsThisMonthHasApproximation ? '≈ ' : '').'MYR '.$donationsThisMonth"
                :note="'This month · '.($donationsMomChange >= 0 ? '▲ ' : '▼ ').abs($donationsMomChange).'% vs last month'"
            />
            <x-admin.metric-card
                icon="heroicon-o-receipt-percent"
                label="Processing fees this month"
                :value="'MYR '.$processingFeesThisMonth"
                :note="'This month · '.($processingFeesMomChange >= 0 ? '▲ ' : '▼ ').abs($processingFeesMomChange).'% vs last month'"
            />
        </div>

        {{-- Operational Alerts --}}
        @if ($pendingBlockedDonations > 0 || $pastDueSubscriptions > 0 || $awaitingStripeOnboarding > 0 || $pendingOrganizations > 0)
        <x-filament::section>
            <x-slot name="heading">Action Required</x-slot>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @if ($pendingOrganizations > 0)
                <a href="{{ route('filament.admin.resources.organizations.index', ['tableFilters[status][value]' => 'pending']) }}" class="ihsan-admin-alert-tile ihsan-admin-alert-tile--warning">
                    <div class="text-2xl font-bold">{{ $pendingOrganizations }}</div>
                    <div class="mt-1 text-sm">Organizations pending approval</div>
                </a>
                @endif
                @if ($pendingBlockedDonations > 0)
                <a href="{{ route('filament.admin.pages.fraud-prevention') }}" class="ihsan-admin-alert-tile ihsan-admin-alert-tile--danger">
                    <div class="text-2xl font-bold">{{ $pendingBlockedDonations }}</div>
                    <div class="mt-1 text-sm">Blocked donations to review</div>
                </a>
                @endif
                @if ($pastDueSubscriptions > 0)
                <div class="ihsan-admin-alert-tile ihsan-admin-alert-tile--danger">
                    <div class="text-2xl font-bold">{{ $pastDueSubscriptions }}</div>
                    <div class="mt-1 text-sm">Past-due subscriptions</div>
                </div>
                @endif
                @if ($awaitingStripeOnboarding > 0)
                <a href="{{ route('filament.admin.resources.organizations.index', ['tableFilters[stripe_onboarded][value]' => '0']) }}" class="ihsan-admin-alert-tile ihsan-admin-alert-tile--info">
                    <div class="text-2xl font-bold">{{ $awaitingStripeOnboarding }}</div>
                    <div class="mt-1 text-sm">Active orgs awaiting Stripe</div>
                </a>
                @endif
            </div>
        </x-filament::section>
        @endif

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
                <div class="mt-3 grid grid-cols-2 gap-3">
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold text-sky-700 dark:text-sky-300">{{ $newOrganizationsThisMonth }}</div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">New this month</div>
                    </div>
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold text-violet-700 dark:text-violet-300">{{ $stripeOnboardedOrganizations }}</div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">Stripe onboarded</div>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-ihsan-muted dark:text-stone-400">Top 5 by donations · All time</div>
                    <div class="ihsan-admin-list">
                        @forelse ($topOrganizations as $org)
                            <div class="ihsan-admin-list-row">
                                <span class="truncate text-sm font-medium text-ihsan-ink dark:text-white">{{ $org['name'] }}</span>
                                <span class="shrink-0 text-sm font-semibold text-ihsan-ink dark:text-white">{{ ($topOrganizationsHaveApproximation ? '≈ ' : '').$org['total'] }}</span>
                            </div>
                        @empty
                            <div class="py-3 text-sm text-ihsan-muted dark:text-stone-400">No data yet.</div>
                        @endforelse
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

        {{-- Donor & Subscription Health --}}
        <div class="grid gap-4 xl:grid-cols-2">
            <x-filament::section>
                <x-slot name="heading">Donor Health</x-slot>
                <div class="grid grid-cols-3 gap-3">
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold text-sky-700 dark:text-sky-300">{{ $newDonorsThisMonth }}</div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">New donors this month</div>
                    </div>
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold text-ihsan-ink dark:text-white">{{ $newDonorsLastMonth }}</div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">New donors last month</div>
                    </div>
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold {{ $newDonorsMomChange >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                            {{ $newDonorsMomChange >= 0 ? '▲' : '▼' }} {{ abs($newDonorsMomChange) }}%
                        </div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">MoM change</div>
                    </div>
                </div>
                <div class="mt-3 ihsan-admin-stat-tile">
                    <div class="text-lg font-semibold text-violet-700 dark:text-violet-300">{{ $repeatDonorRate }}%</div>
                    <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">Repeat donor rate · All time (2+ donations)</div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Subscription Health</x-slot>
                <div class="grid grid-cols-3 gap-3">
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold text-emerald-700 dark:text-emerald-300">+{{ $newSubscriptionsThisMonth }}</div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">New this month</div>
                    </div>
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold text-red-700 dark:text-red-300">-{{ $cancelledSubscriptionsThisMonth }}</div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">Cancelled this month</div>
                    </div>
                    <div class="ihsan-admin-stat-tile">
                        <div class="text-lg font-semibold {{ $netSubscriptionChange >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-300' }}">
                            {{ $netSubscriptionChange >= 0 ? '+' : '' }}{{ $netSubscriptionChange }}
                        </div>
                        <div class="mt-1 text-xs text-ihsan-muted dark:text-stone-400">Net change</div>
                    </div>
                </div>
            </x-filament::section>
        </div>

        {{-- Recurring Health --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    Recurring Health

                    @if ($recurringHealthLastProcess !== 'Never')
                        <span class="inline-flex h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    @endif
                </div>
            </x-slot>

            <x-slot name="afterHeader">
                <div class="flex items-center gap-2 text-sm text-ihsan-muted dark:text-stone-400">
                    <x-heroicon-o-clock class="size-4" />
                    <span>Last process:</span>
                    <span class="font-medium text-ihsan-ink dark:text-white">{{ $recurringHealthLastProcess }}</span>
                </div>
            </x-slot>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-admin.stat-tile
                    icon="heroicon-o-calendar"
                    label="Today's Due"
                    :value="$recurringHealthDueToday"
                    tone="sky"
                />
                <x-admin.stat-tile
                    icon="heroicon-o-check-circle"
                    label="Success"
                    :value="$recurringHealthSuccessToday"
                    tone="emerald"
                />
                <x-admin.stat-tile
                    icon="heroicon-o-arrow-path"
                    label="Retrying"
                    :value="$recurringHealthRetrying"
                    tone="amber"
                />
                <x-admin.stat-tile
                    icon="heroicon-o-x-circle"
                    label="Failed"
                    :value="$recurringHealthFailed"
                    tone="red"
                />
            </div>
        </x-filament::section>

        <div class="grid gap-6 xl:grid-cols-2">
            @livewire(\App\Filament\Widgets\DonationTrendChart::class)
            @livewire(\App\Filament\Widgets\SubscriptionMrrTrendChart::class)
            @livewire(\App\Filament\Widgets\ProcessingFeeTrendChart::class)
            @livewire(\App\Filament\Widgets\DonationSuccessRateChart::class)
            @livewire(\App\Filament\Widgets\TopOrganizationsChart::class)
            @livewire(\App\Filament\Widgets\OrganizationGrowthChart::class)
            @livewire(\App\Filament\Widgets\PaymentMethodChart::class)
        </div>
    </div>
</x-filament-panels::page>
