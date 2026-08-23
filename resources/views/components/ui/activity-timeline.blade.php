{{-- resources/views/components/ui/activity-timeline.blade.php --}}
@props([
    /** @var iterable<\Spatie\Activitylog\Models\Activity> */
    'activities',
    /** Show which record each entry is about. Off on a record's own page, where it would repeat the title. */
    'showSubject' => false,
    /** Events kept behind a toggle: step-by-step noise nobody reads until a payment breaks. */
    'quietEvents' => [],
    'emptyTitle' => 'No activity found',
    'emptyDescription' => 'Activity for this record will appear here.',
])

@php
    $quietCount = collect($activities)
        ->filter(fn ($activity) => in_array((string) ($activity->event ?? ''), $quietEvents, true))
        ->count();
@endphp

<div x-data="{ showQuiet: false }">
    @if (count($activities) > 0)
        @if ($quietCount > 0)
            <div class="flex justify-end border-b border-slate-100 px-5 py-2">
                <button
                    type="button"
                    @click="showQuiet = ! showQuiet"
                    class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-700"
                >
                    <x-heroicon-o-adjustments-horizontal class="size-4" />
                    <span x-show="! showQuiet">Show {{ $quietCount }} processing {{ Str::plural('step', $quietCount) }}</span>
                    <span x-show="showQuiet" x-cloak>Hide processing steps</span>
                </button>
            </div>
        @endif

        <div class="divide-y divide-slate-100">
            @foreach ($activities as $activity)
                @php
                    $isQuiet = in_array((string) ($activity->event ?? ''), $quietEvents, true);
                    $isFailure = \App\Services\ActivityPresenter::isFailure($activity);
                    $transition = \App\Services\ActivityPresenter::statusTransition($activity);
                    $eventSource = \App\Services\ActivityPresenter::eventSource($activity);
                    $failureReason = \App\Services\ActivityPresenter::failureReason($activity);
                    $changes = \App\Services\ActivityPresenter::changedAttributes($activity);
                @endphp

                <div
                    wire:key="activity-{{ $activity->id }}"
                    x-data="{ open: {{ $isFailure ? 'true' : 'false' }}, copied: false }"
                    @if ($isQuiet) x-show="showQuiet" x-collapse x-cloak @endif
                    class="group"
                >
                    <button
                        type="button"
                        @click="open = ! open"
                        class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left hover:bg-slate-50"
                    >
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-chevron-down
                                class="mt-0.5 size-4 shrink-0 text-slate-400 transition-transform"
                                ::class="open ? 'rotate-180' : ''"
                            />
                            <div>
                                <p class="text-sm font-medium text-slate-900">
                                    {{ $activity->description }}
                                </p>
                                @if ($showSubject)
                                    <p class="mt-0.5 text-xs text-slate-500">
                                        {{ \App\Services\ActivityPresenter::subjectTypeLabel($activity) }}
                                        @if ($activity->subject && \App\Services\ActivityPresenter::subjectUrl($activity))
                                            <a
                                                href="{{ \App\Services\ActivityPresenter::subjectUrl($activity) }}"
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
                                @elseif ($failureReason !== null)
                                    <p class="mt-0.5 text-xs text-rose-600">{{ $failureReason }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="whitespace-nowrap text-right">
                            <p class="text-sm text-slate-500">{{ myrTime($activity->created_at) }}</p>
                            <x-ui.badge
                                status="{{ \App\Services\ActivityPresenter::eventColor($activity) }}"
                                size="sm"
                                class="mt-1"
                            >
                                {{ \App\Services\ActivityPresenter::eventLabel($activity) }}
                            </x-ui.badge>
                        </div>
                    </button>

                    {{-- Expanded Details --}}
                    <div
                        x-show="open"
                        x-collapse
                        class="border-t border-slate-100 bg-slate-50/60 px-5 py-4"
                    >
                        @if ($transition !== null && ($transition['from'] !== null || $transition['to'] !== null))
                            <div class="mb-4 rounded-lg bg-white px-4 py-3 shadow-sm ring-1 ring-slate-200">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-[160px_1fr]">
                                    <dt class="text-sm font-medium text-slate-500">Status</dt>
                                    <dd class="flex items-center gap-2 text-sm text-slate-900">
                                        @if ($transition['from'] !== null)
                                            <x-ui.badge status="{{ \App\Services\ActivityPresenter::statusColor($transition['from']) }}" size="sm">
                                                {{ Str::title(str_replace('_', ' ', $transition['from'])) }}
                                            </x-ui.badge>
                                            <x-heroicon-o-arrow-right class="size-4 text-slate-400" />
                                        @endif
                                        @if ($transition['to'] !== null)
                                            <x-ui.badge status="{{ \App\Services\ActivityPresenter::statusColor($transition['to']) }}" size="sm">
                                                {{ Str::title(str_replace('_', ' ', $transition['to'])) }}
                                            </x-ui.badge>
                                        @endif
                                    </dd>
                                </div>
                            </div>
                        @endif

                        <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-[160px_1fr]">
                            <dt class="text-sm font-medium text-slate-500">Initiator</dt>
                            <dd class="text-sm text-slate-900">{{ \App\Services\ActivityPresenter::initiatorName($activity) }}</dd>

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

                            @if ($eventSource !== null)
                                <dt class="text-sm font-medium text-slate-500">Event source</dt>
                                <dd class="text-sm text-slate-900">{{ $eventSource }}</dd>
                            @endif

                            @if ($failureReason !== null)
                                <dt class="text-sm font-medium text-slate-500">Reason</dt>
                                <dd class="text-sm text-slate-900">{{ $failureReason }}</dd>
                            @endif

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

        {{ $slot }}
    @else
        <div class="p-8">
            <x-ui.empty-state
                icon="heroicon-o-clipboard-document-list"
                :title="$emptyTitle"
                :description="$emptyDescription"
            />
        </div>
    @endif
</div>
