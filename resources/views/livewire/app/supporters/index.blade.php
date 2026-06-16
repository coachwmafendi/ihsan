{{-- resources/views/livewire/app/supporters/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Supporters</h1>
            <p class="mt-1 text-sm text-slate-500">Manage and view all supporters</p>
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Total Supporters" value="{{ number_format($this->totalCount) }}" />
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name or email..."
                class="h-10 w-64 rounded-lg border border-slate-300 bg-white pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
        </div>
    </div>

    {{-- Donors Table --}}
    <x-ui.card>
        @if ($this->donors->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('name')" class="group inline-flex items-center gap-1">
                                    Name
                                    @if ($sortField === 'name')
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
                                <button wire:click="sortBy('email')" class="group inline-flex items-center gap-1">
                                    Email
                                    @if ($sortField === 'email')
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
                                <button wire:click="sortBy('donations_count')" class="group inline-flex items-center gap-1">
                                    Donations
                                    @if ($sortField === 'donations_count')
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
                                <button wire:click="sortBy('lifetime_report_amount')" class="group inline-flex items-center gap-1">
                                    Lifetime donated
                                    @if ($sortField === 'lifetime_report_amount')
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
                                <button wire:click="sortBy('donations_min_created_at')" class="group inline-flex items-center gap-1">
                                    First Donation
                                    @if ($sortField === 'donations_min_created_at')
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
                                <button wire:click="sortBy('donations_max_created_at')" class="group inline-flex items-center gap-1">
                                    Last Donation
                                    @if ($sortField === 'donations_max_created_at')
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
                                <button wire:click="sortBy('created_at')" class="group inline-flex items-center gap-1">
                                    Created
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($this->donors as $donor)
                            <tr
                                class="cursor-pointer transition-colors hover:bg-slate-50"
                                onclick="window.location='{{ route('app.supporters.show', $donor) }}'"
                            >
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $donor->name }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ $donor->email }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-900">
                                    {{ number_format($donor->donations_count) }}
                                </td>
                                <td class="px-5 py-4">
                                    @if ($donor->has_report_approximation)
                                        <span class="text-sm font-semibold text-slate-900" title="Includes donations converted from foreign currencies">≈ MYR {{ number_format((float) $donor->lifetime_report_amount, 2) }}</span>
                                    @else
                                        <span class="text-sm font-semibold text-slate-900">MYR {{ number_format((float) $donor->lifetime_report_amount, 2) }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ $donor->donations_min_created_at ? \Carbon\Carbon::parse($donor->donations_min_created_at)->format('M d, Y') : '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ $donor->donations_max_created_at ? \Carbon\Carbon::parse($donor->donations_max_created_at)->format('M d, Y') : '-' }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ $donor->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $this->donors->links() }}
            </div>
        @else
            <x-ui.empty-state
                icon="heroicon-o-users"
                title="No supporters found"
                description="Try adjusting your search criteria."
            />
        @endif
    </x-ui.card>
</div>
