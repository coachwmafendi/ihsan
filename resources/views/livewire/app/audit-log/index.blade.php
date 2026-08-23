{{-- resources/views/livewire/app/audit-log/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Audit log</h1>
        <p class="mt-1 max-w-3xl text-sm text-slate-500">
            See a comprehensive record of changes made across the platform. Click on an event to view detailed information.
        </p>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3 sm:flex-nowrap">
        <div class="relative w-full sm:w-64">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search activity..."
                class="h-10 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
        </div>

        <x-ui.select wire:model.live="period" class="h-10 w-full sm:w-40">
            <flux:select.option value="all_time">All Time</flux:select.option>
            <flux:select.option value="today">Today</flux:select.option>
            <flux:select.option value="yesterday">Yesterday</flux:select.option>
            <flux:select.option value="7_days">Last 7 days</flux:select.option>
            <flux:select.option value="30_days">Last 30 days</flux:select.option>
            <flux:select.option value="90_days">Last 90 days</flux:select.option>
            <flux:select.option value="this_month">This month</flux:select.option>
        </x-ui.select>

        <x-ui.select wire:model.live="eventFilter" class="h-10 w-full sm:w-48">
            <flux:select.option value="">All Events</flux:select.option>
            @foreach ($eventOptions as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </x-ui.select>

        <x-ui.select wire:model.live="subjectTypeFilter" class="h-10 w-full sm:w-40">
            <flux:select.option value="">All Records</flux:select.option>
            @foreach ($subjectTypeOptions as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </x-ui.select>

        <x-ui.select wire:model.live="initiatorFilter" class="h-10 w-full sm:w-40">
            @foreach ($initiatorOptions as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </x-ui.select>

        @if ($this->hasActiveFilters)
            <button
                type="button"
                wire:click="resetFilters"
                class="inline-flex items-center gap-1.5 whitespace-nowrap text-sm font-medium text-blue-600 hover:text-blue-700"
            >
                <x-heroicon-o-x-mark class="size-4" />
                Reset filters
            </button>
        @endif
    </div>

    {{-- Says whether the list is everything or only what the filters let through. --}}
    <p class="-mt-3 text-sm text-slate-500">
        {{ number_format($this->activities->total()) }}
        {{ Str::plural('entry', $this->activities->total()) }}
        @if ($this->hasActiveFilters)
            matching the current filters
        @endif
    </p>

    {{-- Activity List --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <x-ui.activity-timeline
            :activities="$this->activities"
            :show-subject="true"
            :empty-description="$this->hasActiveFilters
                ? 'Try adjusting your filters or search criteria.'
                : 'Activity across your campaigns, donations and supporters will appear here.'"
        >
            <div class="border-t border-slate-100 px-5 py-3">
                {{ $this->activities->links() }}
            </div>
        </x-ui.activity-timeline>
    </div>
</div>
