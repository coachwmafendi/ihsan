<x-filament::page>
    @php
        $stats = $this->stats;
    @endphp

    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Fraud Prevention Dashboard</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">Monitor and manage suspicious donation activity.</p>
            </div>
            <div class="flex items-center gap-2">
                <select
                    wire:model.live="period"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                >
                    <option value="today">Today</option>
                    <option value="yesterday">Yesterday</option>
                    <option value="last_7_days">Last 7 Days</option>
                    <option value="last_30_days">Last 30 Days</option>
                    <option value="this_month">This Month</option>
                </select>
            </div>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Donations</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['total']) }}</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Blocked</p>
                <p class="mt-2 text-2xl font-bold text-red-600">{{ number_format($stats['blocked']) }}</p>
                <p class="mt-1 text-xs text-gray-400">{{ $stats['block_rate'] }}% of total</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Flagged</p>
                <p class="mt-2 text-2xl font-bold text-amber-600">{{ number_format($stats['flagged']) }}</p>
                <p class="mt-1 text-xs text-gray-400">Requires review</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">High Risk</p>
                <p class="mt-2 text-2xl font-bold text-red-600">{{ number_format($stats['high_risk']) }}</p>
                <p class="mt-1 text-xs text-gray-400">Risk score ≥ 65</p>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Block Rate</p>
                <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $stats['block_rate'] }}%</p>
                <p class="mt-1 text-xs text-gray-400">Of total donations</p>
            </div>
        </div>

        {{-- Blocked Donations Table --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Blocked Donations</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Donations automatically blocked by fraud rules.</p>
            </div>
            <div class="p-5">
                {{ $this->table }}
            </div>
        </div>

        {{-- Recent Fraud Attempts --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Recent Fraud Attempts</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Latest attempts that triggered fraud rules.</p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($this->recentAttempts as $attempt)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex size-8 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                                <x-heroicon-o-shield-exclamation class="size-4 text-red-600 dark:text-red-400" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $attempt['email'] ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $attempt['reason'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ strtoupper($attempt['currency']) }} {{ number_format((float) $attempt['amount'], 2) }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $attempt['created_at'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center">
                        <x-heroicon-o-check-circle class="mx-auto size-8 text-gray-400 dark:text-gray-600" />
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No fraud attempts recorded.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Fraud Rules --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Fraud Rules</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Active rules and their configurations.</p>
            </div>
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse ($this->rules as $rule)
                    <div class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <div class="flex size-8 items-center justify-center rounded-full {{ $rule['is_active'] ? 'bg-green-100 dark:bg-green-900/30' : 'bg-gray-100 dark:bg-gray-800' }}">
                                <x-heroicon-o-check-circle class="size-4 {{ $rule['is_active'] ? 'text-green-600 dark:text-green-400' : 'text-gray-400 dark:text-gray-500' }}" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $rule['type'] }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $rule['config'] }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                {{ match($rule['action']) {
                                    'block' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                    'flag' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400',
                                } }}">
                                {{ ucfirst($rule['action']) }}
                            </span>
                            <p class="mt-1 text-xs text-gray-400">{{ $rule['organization'] }}</p>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-8 text-center">
                        <x-heroicon-o-cog-6-tooth class="mx-auto size-8 text-gray-400 dark:text-gray-600" />
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No fraud rules configured.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament::page>
