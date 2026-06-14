{{-- resources/views/livewire/app/donations/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Donations</h1>
            <p class="mt-1 text-sm text-slate-500">Track and manage all donations</p>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Total Donations" value="{{ number_format($this->totalCount) }}" />
        <x-ui.stat-card label="Total Amount" value="{{ $this->totalAmount }}" />
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3 sm:flex-nowrap">
        <div class="relative w-full sm:w-64">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search donors..."
                class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
        </div>

        <x-ui.select wire:model.live="period" class="h-10 w-full sm:w-44">
            <flux:select.option value="all_time">All Time</flux:select.option>
            <flux:select.option value="today">Today</flux:select.option>
            <flux:select.option value="yesterday">Yesterday</flux:select.option>
            <flux:select.option value="7_days">Last 7 days</flux:select.option>
            <flux:select.option value="30_days">Last 30 days</flux:select.option>
            <flux:select.option value="90_days">Last 90 days</flux:select.option>
            <flux:select.option value="this_month">This month</flux:select.option>
        </x-ui.select>

        <x-ui.select wire:model.live="statusFilter" class="h-10 w-full sm:w-40">
            <flux:select.option value="">All Statuses</flux:select.option>
            <flux:select.option value="succeeded">Succeeded</flux:select.option>
            <flux:select.option value="pending">Pending</flux:select.option>
            <flux:select.option value="failed">Failed</flux:select.option>
            <flux:select.option value="refunded">Refunded</flux:select.option>
        </x-ui.select>
    </div>

    {{-- Donations Table --}}
    <x-ui.card>
        @if ($this->donations->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1">
                                    Date
                                    @if ($sortField === 'created_at')
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
                                <button wire:click="sortBy('donor_name')" class="group inline-flex items-center gap-1">
                                    Donor
                                    @if ($sortField === 'donor_name')
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
                                <button wire:click="sortBy('gross_amount')" class="group inline-flex items-center gap-1">
                                    Amount
                                    @if ($sortField === 'gross_amount')
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
                                <button wire:click="sortBy('campaign')" class="group inline-flex items-center gap-1">
                                    Campaign
                                    @if ($sortField === 'campaign')
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
                        @foreach ($this->donations as $donation)
                            <tr
                                class="cursor-pointer transition-colors hover:bg-slate-50"
                                onclick="window.location='{{ route('app.donations.show', $donation) }}'"
                            >
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ $donation->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-50">
                                            <x-heroicon-o-user class="size-4 text-slate-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ $donation->donor?->name ?? 'Anonymous' }}</p>
                                            @if ($donation->donor?->email)
                                                <p class="text-xs text-slate-500">{{ $donation->donor->email }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-sm font-semibold text-slate-900">
                                    {{ $donation->formatted_amount }}
                                </td>
                                <td class="px-5 py-4">
                                    <x-ui.badge status="{{ $donation->status->value }}" size="sm">
                                        {{ ucfirst($donation->status->value === 'succeeded' ? 'Succeeded' : $donation->status->value) }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    @if ($donation->campaign)
                                        <a
                                            href="{{ route('app.campaigns.edit', $donation->campaign) }}"
                                            wire:navigate.stop
                                            class="hover:text-teal-600"
                                            onclick="event.stopPropagation()"
                                        >
                                            {{ $donation->campaign->title }}
                                        </a>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $this->donations->links() }}
            </div>
        @else
            <x-ui.empty-state
                icon="heroicon-o-banknotes"
                title="No donations found"
                description="Try adjusting your filters or search criteria."
            />
        @endif
    </x-ui.card>
</div>
