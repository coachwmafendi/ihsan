{{-- resources/views/livewire/app/subscriptions/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Subscriptions</h1>
            <p class="mt-1 text-sm text-slate-500">Manage recurring donations and subscriptions</p>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Total Subscriptions" value="{{ number_format($this->totalCount) }}" />
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by donor..."
                class="h-10 w-64 rounded-lg border border-slate-300 bg-white pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
        </div>

        <x-ui.select wire:model.live="statusFilter" class="h-10">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="active">Active</flux:select.option>
            <flux:select.option value="cancelled">Cancelled</flux:select.option>
            <flux:select.option value="paused">Paused</flux:select.option>
            <flux:select.option value="past_due">Past Due</flux:select.option>
        </x-ui.select>
    </div>

    {{-- Subscriptions Table --}}
    <x-ui.card>
        @if ($this->subscriptions->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('donor')" class="group inline-flex items-center gap-1">
                                    Donor
                                    @if ($sortField === 'donor')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="size-3 text-slate-400" />
                                        @else
                                            <x-heroicon-o-chevron-down class="size-3 text-slate-400" />
                                        @endif
                                    @else
                                        <x-heroicon-o-chevron-up-down class="size-3 text-slate-300" />
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('amount')" class="group inline-flex items-center gap-1">
                                    Amount
                                    @if ($sortField === 'amount')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="size-3 text-slate-400" />
                                        @else
                                            <x-heroicon-o-chevron-down class="size-3 text-slate-400" />
                                        @endif
                                    @else
                                        <x-heroicon-o-chevron-up-down class="size-3 text-slate-300" />
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('interval')" class="group inline-flex items-center gap-1">
                                    Frequency
                                    @if ($sortField === 'interval')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="size-3 text-slate-400" />
                                        @else
                                            <x-heroicon-o-chevron-down class="size-3 text-slate-400" />
                                        @endif
                                    @else
                                        <x-heroicon-o-chevron-up-down class="size-3 text-slate-300" />
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('status')" class="group inline-flex items-center gap-1">
                                    Status
                                    @if ($sortField === 'status')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="size-3 text-slate-400" />
                                        @else
                                            <x-heroicon-o-chevron-down class="size-3 text-slate-400" />
                                        @endif
                                    @else
                                        <x-heroicon-o-chevron-up-down class="size-3 text-slate-300" />
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('current_period_end')" class="group inline-flex items-center gap-1">
                                    Next Billing
                                    @if ($sortField === 'current_period_end')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="size-3 text-slate-400" />
                                        @else
                                            <x-heroicon-o-chevron-down class="size-3 text-slate-400" />
                                        @endif
                                    @else
                                        <x-heroicon-o-chevron-up-down class="size-3 text-slate-300" />
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('current_period_start')" class="group inline-flex items-center gap-1">
                                    Started
                                    @if ($sortField === 'current_period_start')
                                        @if ($sortDirection === 'asc')
                                            <x-heroicon-o-chevron-up class="size-3 text-slate-400" />
                                        @else
                                            <x-heroicon-o-chevron-down class="size-3 text-slate-400" />
                                        @endif
                                    @else
                                        <x-heroicon-o-chevron-up-down class="size-3 text-slate-300" />
                                    @endif
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($this->subscriptions as $subscription)
                            <tr
                                class="cursor-pointer transition-colors hover:bg-slate-50"
                                onclick="window.location='{{ route('app.subscriptions.show', $subscription) }}'"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-50">
                                            <x-heroicon-o-user class="size-4 text-slate-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ $subscription->donor?->name ?? 'Unknown' }}</p>
                                            @if ($subscription->donor?->email)
                                                <p class="text-xs text-slate-500">{{ $subscription->donor->email }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700">
                                        {{ $subscription->currency_symbol }} {{ number_format((float) $subscription->amount, 2) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ ucfirst($subscription->interval->value) }}
                                </td>
                                <td class="px-5 py-4">
                                    <x-ui.badge status="{{ $subscription->status->value }}" size="sm">
                                        {{ $subscription->status->getLabel() }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ $subscription->current_period_end?->format('M d, Y') ?? '—' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ $subscription->current_period_start?->format('M d, Y') ?? '—' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $this->subscriptions->links() }}
            </div>
        @else
            <x-ui.empty-state
                icon="heroicon-o-arrow-path"
                title="No subscriptions found"
                description="Try adjusting your search or filter criteria."
            />
        @endif
    </x-ui.card>
</div>
