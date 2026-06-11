<x-filament-panels::page>
    <div class="space-y-8">
        <x-filament::section>
            <div class="space-y-8">
                {{ $this->form }}

                <div class="border-t border-gray-200 pt-6">
                    <x-filament::button
                        wire:click="sendNotification"
                        wire:loading.label="Sending..."
                        icon="heroicon-o-paper-airplane"
                    >
                        Send Notification
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        @php
            $sentNotifications = $this->getSentNotifications();
        @endphp

        @if ($sentNotifications->isNotEmpty())
            <x-filament::section>
                <x-slot name="heading">
                    Sent Notifications
                </x-slot>

                <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Message</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Recipients</th>
                                <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Sent</th>
                                <th scope="col" class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach ($sentNotifications as $notification)
                                @php
                                    $data = $notification->data;
                                    $type = $data['type'] ?? 'info';
                                    $badgeColors = [
                                        'error' => 'bg-red-50 text-red-600',
                                        'success' => 'bg-green-50 text-green-600',
                                        'warning' => 'bg-yellow-50 text-yellow-600',
                                        'info' => 'bg-teal-50 text-teal-600',
                                    ];
                                    $badgeColor = $badgeColors[$type] ?? $badgeColors['info'];
                                @endphp
                                <tr
                                    x-data="{ expanded: false }"
                                    class="hover:bg-gray-50"
                                >
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badgeColor }}">
                                            {{ ucfirst($type) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <div
                                            class="cursor-pointer"
                                            @click="expanded = !expanded"
                                        >
                                            <p
                                                x-show="!expanded"
                                                x-transition
                                                class="max-w-xs truncate text-sm text-gray-700"
                                            >
                                                {{ $data['message'] ?? '' }}
                                            </p>
                                            <p
                                                x-show="expanded"
                                                x-transition
                                                class="whitespace-normal text-sm text-gray-700"
                                            >
                                                {{ $data['message'] ?? '' }}
                                            </p>
                                        </div>
                                        @if ($data['image'] ?? null)
                                            <span class="mt-1 inline-flex items-center text-xs text-gray-400">
                                                <x-heroicon-o-photo class="mr-1 size-3" />
                                                Has image
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500">
                                        @php
                                            $orgId = $data['organization_id'] ?? null;
                                            $orgName = $orgId ? \App\Models\Organization::find($orgId)?->name ?? 'Unknown' : 'All orgs';
                                        @endphp
                                        {{ $orgName }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-sm text-gray-500">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right">
                                        <button
                                            wire:click="deleteNotification('{{ $notification->id }}')"
                                            wire:confirm="Are you sure you want to delete this notification?"
                                            type="button"
                                            class="text-sm font-medium text-red-600 hover:text-red-700"
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $sentNotifications->links() }}
                </div>
            </x-filament::section>
        @endif
    </div>
</x-filament-panels::page>
