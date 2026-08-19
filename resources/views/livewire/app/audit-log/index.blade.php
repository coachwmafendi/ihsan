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
        @if ($this->activities->isNotEmpty())
            <div class="divide-y divide-slate-100">
                @foreach ($this->activities as $activity)
                    <div
                        x-data="{ open: false, copied: false }"
                        class="group"
                    >
                        <button
                            type="button"
                            @click="open = ! open"
                            class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50"
                        >
                            <div class="flex items-start gap-3">
                                <x-heroicon-o-chevron-down
                                    class="mt-0.5 size-4 text-slate-400 transition-transform"
                                    ::class="open ? 'rotate-180' : ''"
                                />
                                <div>
                                    <p class="text-sm font-medium text-slate-900">
                                        {{ $activity->description }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ $this->subjectTypeLabel($activity) }}
                                        @if ($activity->subject && $this->subjectUrl($activity))
                                            <a
                                                href="{{ $this->subjectUrl($activity) }}"
                                                wire:navigate
                                                class="ml-1 text-blue-600 hover:text-blue-700"
                                                @click.stop
                                            >
                                                {{ $activity->subject->public_id ?? ('#'.$activity->subject->getKey()) }}
                                            </a>
                                        @elseif ($activity->subject)
                                            <span class="ml-1">{{ $activity->subject->public_id ?? ('#'.$activity->subject->getKey()) }}</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="whitespace-nowrap text-right">
                                <p class="text-sm text-slate-500">{{ myrTime($activity->created_at) }}</p>
                                <x-ui.badge
                                    status="{{ $this->eventColor($activity) }}"
                                    size="sm"
                                    class="mt-1"
                                >
                                    {{ $this->eventLabel($activity) }}
                                </x-ui.badge>
                            </div>
                        </button>

                        {{-- Expanded Details --}}
                        <div
                            x-show="open"
                            x-collapse
                            class="border-t border-slate-100 bg-slate-50/60 px-5 py-4"
                        >
                            @php($transition = $this->statusTransition($activity))
                            @if ($transition !== null && ($transition['from'] !== null || $transition['to'] !== null))
                                <div class="mb-4 rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200">
                                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-[160px_1fr]">
                                        <dt class="text-sm font-medium text-slate-500">Status</dt>
                                        <dd class="flex items-center gap-2 text-sm text-slate-900">
                                            @if ($transition['from'] !== null)
                                                <x-ui.badge status="{{ $this->statusColor($transition['from']) }}" size="sm">
                                                    {{ Str::title(str_replace('_', ' ', $transition['from'])) }}
                                                </x-ui.badge>
                                                <x-heroicon-o-arrow-right class="size-4 text-slate-400" />
                                            @endif
                                            @if ($transition['to'] !== null)
                                                <x-ui.badge status="{{ $this->statusColor($transition['to']) }}" size="sm">
                                                    {{ Str::title(str_replace('_', ' ', $transition['to'])) }}
                                                </x-ui.badge>
                                            @endif
                                        </dd>
                                    </div>
                                </div>
                            @endif

                            <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-[160px_1fr]">
                                <dt class="text-sm font-medium text-slate-500">Initiator</dt>
                                <dd class="text-sm text-slate-900">{{ $this->initiatorName($activity) }}</dd>

                                <dt class="text-sm font-medium text-slate-500">Log ID</dt>
                                <dd class="flex items-center gap-2 text-sm text-slate-900">
                                    <span class="font-mono">{{ $activity->id }}</span>
                                    <button
                                        type="button"
                                        @click="navigator.clipboard.writeText('{{ $activity->id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-200 hover:text-slate-600"
                                    >
                                        <x-heroicon-o-clipboard-document class="size-3.5" />
                                    </button>
                                    <span x-show="copied" x-transition class="text-xs text-emerald-600">Copied!</span>
                                </dd>

                                @php($eventSource = $this->eventSource($activity))
                                @if ($eventSource !== null)
                                    <dt class="text-sm font-medium text-slate-500">Event source</dt>
                                    <dd class="text-sm text-slate-900">{{ $eventSource }}</dd>
                                @endif

                                @php($changes = $this->changedAttributes($activity))
                                @if (count($changes))
                                    <dt class="text-sm font-medium text-slate-500">Changes</dt>
                                    <dd class="text-sm text-slate-900">
                                        {{-- Both values are shown: an audit trail that hides what
                                             something used to be is only half a record. --}}
                                        <ul class="space-y-1.5">
                                            @foreach ($changes as $change)
                                                <li class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                                                    <span class="font-medium">{{ str_replace('_', ' ', $change['field']) }}</span>
                                                    <span class="break-all text-slate-400 line-through">{{ $change['old'] }}</span>
                                                    <x-heroicon-o-arrow-right class="size-3.5 shrink-0 text-slate-400" />
                                                    <span class="break-all font-medium text-slate-900">{{ $change['new'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </dd>
                                @endif
                            </dl>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $this->activities->links() }}
            </div>
        @else
            <div class="p-8">
                <x-ui.empty-state
                    icon="heroicon-o-clipboard-document-list"
                    title="No activity found"
                    :description="$this->hasActiveFilters
                        ? 'Try adjusting your filters or search criteria.'
                        : 'Activity across your campaigns, donations and supporters will appear here.'"
                />
            </div>
        @endif
    </div>
</div>
