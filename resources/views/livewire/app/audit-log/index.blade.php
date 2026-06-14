{{-- resources/views/livewire/app/audit-log/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <x-ui.page-header title="Audit Log">
        <x-slot:subtitle>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-slate-500">
                    <li>Organization</li>
                    <li>
                        <svg class="mx-1 h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </li>
                    <li class="font-medium text-slate-900">Audit Log</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search activity..."
                class="h-10 w-64 rounded-lg border border-slate-300 bg-white pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
        </div>

        <x-ui.select wire:model.live="eventFilter" class="h-10">
            <flux:select.option value="">All Events</flux:select.option>
            @foreach ($eventOptions as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </x-ui.select>

        <x-ui.select wire:model.live="subjectTypeFilter" class="h-10">
            <flux:select.option value="">All Subject Types</flux:select.option>
            @foreach ($subjectTypeOptions as $value => $label)
                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
            @endforeach
        </x-ui.select>

        <x-ui.select wire:model.live="period" class="h-10">
            <flux:select.option value="all_time">All Time</flux:select.option>
            <flux:select.option value="today">Today</flux:select.option>
            <flux:select.option value="yesterday">Yesterday</flux:select.option>
            <flux:select.option value="7_days">Last 7 days</flux:select.option>
            <flux:select.option value="30_days">Last 30 days</flux:select.option>
            <flux:select.option value="90_days">Last 90 days</flux:select.option>
            <flux:select.option value="this_month">This month</flux:select.option>
        </x-ui.select>
    </div>

    {{-- Table --}}
    <x-ui.card>
        @if ($this->activities->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Time
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Actor
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Event
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Activity
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Subject
                            </th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                Changes
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($this->activities as $activity)
                            <tr class="hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-500">
                                    {{ $activity->created_at->format('M d, Y H:i') }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-900">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-slate-100">
                                            <x-heroicon-o-user class="size-3.5 text-slate-500" />
                                        </div>
                                        {{ $this->actorName($activity) }}
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <x-ui.badge status="{{ $activity->event === 'deleted' ? 'danger' : ($activity->event === 'created' ? 'success' : 'default') }}" size="sm">
                                        {{ str_replace('_', ' ', $activity->event ?? 'N/A') }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    {{ $activity->description }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-700">
                                    {{ $this->subjectLabel($activity) }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    @php($changes = $this->changedAttributes($activity))
                                    @if (count($changes))
                                        <ul class="space-y-1">
                                            @foreach (array_slice($changes, 0, 3) as $change)
                                                <li>
                                                    <span class="font-medium">{{ $change['field'] }}</span>
                                                    <span class="text-slate-400">→</span>
                                                    <span class="text-slate-500" title="Old: {{ $change['old'] }}">{{ $change['new'] }}</span>
                                                </li>
                                            @endforeach
                                            @if (count($changes) > 3)
                                                <li class="text-xs text-slate-400">+{{ count($changes) - 3 }} more</li>
                                            @endif
                                        </ul>
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
                {{ $this->activities->links() }}
            </div>
        @else
            <x-ui.empty-state
                icon="heroicon-o-clipboard-document-list"
                title="No activity found"
                description="Try adjusting your filters or search criteria."
            />
        @endif
    </x-ui.card>
</div>
