<x-filament::page>
    <x-ui.page-header title="Notifications" subtitle="Your recent account activity and alerts." />

    @php
        $notifications = auth()->user()->notifications()->latest()->paginate($this->perPage);
    @endphp

    @if ($notifications->isEmpty())
        <x-ui.empty-state
            icon="heroicon-o-bell"
            title="No notifications"
            description="You'll see notifications here when there's activity on your account."
        />
    @else
        <x-filament::section>
            <div class="space-y-6">
                @if (auth()->user()->unread_notifications_count > 0)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-0.5 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-600/20">
                                {{ auth()->user()->unread_notifications_count }} unread
                            </span>
                        </div>

                        <button
                            wire:click="markAllAsRead"
                            type="button"
                            class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                        >
                            Mark all as read
                        </button>
                    </div>
                @endif

                <div class="divide-y divide-gray-100">
                    @foreach ($notifications as $notification)
                        @php
                            $type = $notification->data['type'] ?? 'info';
                            $iconColors = [
                                'error' => 'bg-red-50 text-red-600',
                                'success' => 'bg-green-50 text-green-600',
                                'warning' => 'bg-yellow-50 text-yellow-600',
                                'info' => 'bg-teal-50 text-teal-600',
                            ];
                            $iconClass = $iconColors[$type] ?? $iconColors['info'];
                        @endphp

                        <div
                            x-data="{ expanded: false }"
                            class="group flex gap-4 py-5 transition-colors hover:bg-gray-50/50 {{ $notification->read_at ? '' : 'bg-teal-50/30' }}"
                        >
                            {{-- Icon --}}
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $iconClass }}">
                                @if ($type === 'error')
                                    <x-heroicon-o-exclamation-triangle class="size-5" />
                                @elseif ($type === 'success')
                                    <x-heroicon-o-check-circle class="size-5" />
                                @elseif ($type === 'warning')
                                    <x-heroicon-o-exclamation-circle class="size-5" />
                                @else
                                    <x-heroicon-o-information-circle class="size-5" />
                                @endif
                            </div>

                            {{-- Content --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ ucfirst($type) }}
                                        </p>
                                        <div
                                            class="mt-1 cursor-pointer"
                                            @click="expanded = !expanded"
                                        >
                                            <p
                                                x-show="!expanded"
                                                x-transition
                                                class="text-sm text-gray-600 line-clamp-2"
                                            >
                                                {{ Str::limit($notification->data['message'] ?? '', 120) }}
                                            </p>
                                            <p
                                                x-show="expanded"
                                                x-transition
                                                class="whitespace-normal text-sm text-gray-600"
                                            >
                                                {{ $notification->data['message'] ?? '' }}
                                            </p>

                                            @if ($notification->data['image'] ?? null)
                                                <div x-show="expanded" x-transition class="mt-3">
                                                    <img
                                                        src="{{ Storage::url($notification->data['image']) }}"
                                                        alt="Notification image"
                                                        class="max-h-48 rounded-lg object-cover"
                                                    />
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-end gap-1">
                                        <p class="text-xs text-gray-400">
                                            {{ \Illuminate\Support\Carbon::parse($notification->data['timestamp'] ?? $notification->created_at)->diffForHumans() }}
                                        </p>

                                        @if ($notification->read_at)
                                            <span class="text-xs text-gray-400">Read</span>
                                        @else
                                            <button
                                                wire:click="markAsRead('{{ $notification->id }}')"
                                                type="button"
                                                class="text-xs font-medium text-teal-600 hover:text-teal-700"
                                            >
                                                Mark as read
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between border-t border-gray-100 pt-4">
                    <div class="flex items-center gap-2 text-sm text-gray-500">
                        <span>Show</span>
                        <select wire:model.live="perPage" class="rounded-md border-gray-300 py-1 pl-2 pr-8 text-sm shadow-sm focus:border-teal-500 focus:ring-teal-500">
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                        <span>per page</span>
                    </div>

                    <div class="text-sm">
                        {{ $notifications->links() }}
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif
</x-filament::page>
