{{-- resources/views/livewire/app/notifications/index.blade.php --}}
<div class="space-y-6">
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Notifications</h1>
        <p class="mt-1 text-sm text-slate-500">Updates from the platform admin</p>
    </div>

    <x-ui.card>
        @if ($this->notifications->isNotEmpty())
            <div class="divide-y divide-slate-100">
                @foreach ($this->notifications as $notification)
                    <div
                        wire:key="notification-{{ $notification->id }}"
                        class="flex flex-col gap-3 px-5 py-4 transition-colors {{ $notification->unread() ? 'bg-teal-50/50' : 'bg-white' }}"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <x-ui.badge :status="$notification->data['type'] ?? 'default'" size="sm">
                                    {{ ucfirst($notification->data['type'] ?? 'info') }}
                                </x-ui.badge>

                                @if ($notification->unread())
                                    <span class="size-2 rounded-full bg-teal-600" aria-hidden="true"></span>
                                @endif
                            </div>

                            <span class="text-xs text-slate-500 whitespace-nowrap">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <x-ui.rich-text :text="$notification->data['message'] ?? ''" />

                        @if (! empty($notification->data['image']))
                            <div>
                                <img
                                    src="{{ Storage::url($notification->data['image']) }}"
                                    alt="Notification image"
                                    class="h-32 w-auto rounded-lg border border-slate-200 object-cover"
                                />
                            </div>
                        @endif

                        <div class="flex items-center gap-3">
                            @if ($notification->unread())
                                <button
                                    type="button"
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    class="text-sm font-medium text-teal-600 hover:text-teal-700"
                                >
                                    Mark as read
                                </button>
                            @endif

                            <button
                                type="button"
                                wire:click="delete('{{ $notification->id }}')"
                                class="text-sm font-medium text-red-600 hover:text-red-700"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="px-5 py-4 border-t border-slate-100">
                {{ $this->notifications->links() }}
            </div>
        @else
            <x-ui.empty-state
                icon="heroicon-o-bell-slash"
                title="No notifications"
                description="You don't have any platform notifications yet."
            />
        @endif
    </x-ui.card>
</div>
