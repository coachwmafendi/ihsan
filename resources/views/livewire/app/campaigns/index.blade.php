{{-- resources/views/livewire/app/campaigns/index.blade.php --}}
<div class="space-y-6" x-data="{ openMenuId: null, menuTop: 0, menuLeft: 0, closeIfOutside: null, init() { this.closeIfOutside = (e) => { if (! e.target.closest('[data-action-menu]')) { this.openMenuId = null; } }; document.addEventListener('click', this.closeIfOutside); }, destroy() { document.removeEventListener('click', this.closeIfOutside); }, openMenu(id, el) { const r = el.getBoundingClientRect(); this.menuTop = r.bottom + 4; this.menuLeft = r.right - 208; this.openMenuId = (this.openMenuId === id ? null : id); } }">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                {{ $showArchived ? 'Archived Campaigns' : 'Campaigns' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $showArchived ? 'Restore campaigns to make them available again.' : 'Manage your fundraising campaigns' }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button
                wire:click="toggleArchived"
                type="button"
                class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors
                    {{ $showArchived
                        ? 'border-teal-600 bg-teal-50 text-teal-700 hover:bg-teal-100'
                        : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50' }}"
            >
                <x-heroicon-o-archive-box class="size-4" />
                @if ($showArchived)
                    Active
                @else
                    Archived
                    @if ($this->archivedCount > 0)
                        <span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-xs font-semibold text-slate-600">{{ $this->archivedCount }}</span>
                    @endif
                @endif
            </button>
            @if (! $showArchived)
                <x-ui.button wire:click="openCreateModal" variant="primary">
                    <x-heroicon-o-plus class="size-4" />
                    Create Campaign
                </x-ui.button>
            @endif
        </div>
    </div>

    @if (! $showArchived)
        {{-- Filters --}}
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative">
                <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Search campaigns..."
                    class="h-10 w-64 rounded-lg border border-slate-300 bg-white pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
            </div>

            <x-ui.select wire:model.live="statusFilter" class="h-10 w-40">
                <flux:select.option value="">All Statuses</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="draft">Draft</flux:select.option>
                <flux:select.option value="paused">Paused</flux:select.option>
                <flux:select.option value="ended">Ended</flux:select.option>
            </x-ui.select>
        </div>
    @endif

    {{-- Campaigns Table --}}
    <x-ui.card>
        @if ($this->campaigns->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">
                                <button wire:click="sortBy('title')" class="group inline-flex items-center gap-1">
                                    Title
                                    @if ($sortField === 'title')
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
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">
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
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Raised</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Donations</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">
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
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($this->campaigns as $campaign)
                            <tr
                                wire:key="campaign-row-{{ $campaign->public_id }}"
                                wire:click="redirectToEdit('{{ $campaign->public_id }}')"
                                class="cursor-pointer transition-colors hover:bg-slate-50"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($campaign->image_path && Storage::disk('public')->exists($campaign->image_path))
                                            <img src="{{ Storage::disk('public')->url($campaign->image_path) }}" alt="" class="h-10 w-10 rounded-lg object-cover" />
                                        @else
                                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50">
                                                <x-heroicon-o-megaphone class="size-5 text-teal-600" />
                                            </div>
                                        @endif
                                        <div>
                                            <span class="text-sm font-semibold text-slate-900">
                                                {{ $campaign->title }}
                                            </span>
                                            @if ($campaign->payment_gateway)
                                                <x-ui.badge status="info" size="xs" class="mt-1 w-fit">
                                                    {{ $campaign->payment_gateway->getLabel() }}
                                                </x-ui.badge>
                                            @endif
                                            @if ($campaign->has_target && $campaign->target_amount)
                                                @php
                                                        $pct = $campaign->target_amount > 0
                                                            ? min(100, ($campaign->collected_amount / $campaign->target_amount) * 100)
                                                            : 0;
                                                        $tooltipText = 'RM ' . number_format((float) $campaign->collected_amount, 2) . ' of RM ' . number_format((float) $campaign->target_amount, 2);
                                                    @endphp
                                                <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                                    <x-ui.tooltip :text="$tooltipText">
                                                        <div class="h-2.5 w-28 overflow-hidden rounded-full bg-gray-200 ring-1 ring-gray-300">
                                                            <div class="h-full rounded-full bg-teal-500 transition-all" style="width: {{ $pct }}%"></div>
                                                        </div>
                                                    </x-ui.tooltip>
                                                    <span>{{ number_format($pct, 1) }}%</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <x-ui.badge status="{{ $campaign->status->value }}" size="sm">
                                        {{ ucfirst($campaign->status->value) }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-slate-900">
                                    RM {{ number_format((float) $campaign->collected_amount, 2) }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    {{ number_format($campaign->donations_count) }}
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ myrTime($campaign->created_at, withLabel: false, format: 'M d, Y') }}
                                </td>
                                <td class="px-5 py-4 text-right" wire:click.stop>
                                    @if ($showArchived)
                                        <button
                                            wire:click="restore('{{ $campaign->public_id }}')"
                                            wire:loading.attr="disabled"
                                            type="button"
                                            class="inline-flex items-center gap-1 rounded px-2 py-1 text-sm font-medium text-teal-600 transition-colors hover:bg-teal-50 hover:text-teal-700"
                                        >
                                            <x-heroicon-o-arrow-path class="size-3.5" />
                                            Restore
                                        </button>
                                    @else
                                            <div wire:key="campaign-actions-{{ $campaign->public_id }}" data-action-menu>
                                                <button
                                                    type="button"
                                                    @click.stop="openMenu('{{ $campaign->public_id }}', $el)"
                                                class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                                aria-label="Campaign actions"
                                            >
                                                <x-heroicon-o-ellipsis-horizontal class="size-5" />
                                            </button>

                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $this->campaigns->links() }}
            </div>
        @else
            @if ($showArchived)
                <x-ui.empty-state
                    icon="heroicon-o-archive-box"
                    title="No archived campaigns"
                    description="Archived campaigns will appear here."
                />
            @else
                <x-ui.empty-state
                    icon="heroicon-o-megaphone"
                    title="No campaigns found"
                    description="Get started by creating your first fundraising campaign."
                    action-label="Create Campaign"
                    action-wire-click="openCreateModal"
                />
            @endif
        @endif
    </x-ui.card>

    <livewire:app.campaigns.campaign-create-modal />

    {{-- Rename Campaign Modal --}}
    @if ($showRenameModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="$set('showRenameModal', false)"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Rename Campaign</h2>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="renameTitle" class="block text-sm font-medium text-slate-700">Campaign Title</label>
                        <input type="text" id="renameTitle" wire:model="renameTitle" placeholder="Campaign title" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('renameTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <x-ui.button wire:click="$set('showRenameModal', false)" variant="secondary">Cancel</x-ui.button>
                    <x-ui.button wire:click="saveRename" variant="primary">
                        <span wire:loading.remove wire:target="saveRename">Save</span>
                        <span wire:loading wire:target="saveRename">Saving...</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif

    {{-- Shared row-actions menu: one static teleported instance for all rows.
         Per-row teleports leak stale clones when Livewire morphs re-create the
         row templates, leaving orphaned menus stuck open in <body>. --}}
    <template x-teleport="body">
        <div
            x-show="openMenuId"
            x-cloak
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
            data-action-menu
            class="fixed z-50 w-52 rounded-xl border border-slate-200 bg-white py-1 shadow-lg"
            :style="'top: ' + menuTop + 'px; left: ' + menuLeft + 'px'"
        >
            <button
                type="button"
                @click.stop="navigator.clipboard.writeText(openMenuId).then(() => openMenuId = null)"
                class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
            >
                <x-heroicon-o-clipboard-document class="size-4 text-slate-500" />
                Copy ID
            </button>

            <button
                type="button"
                @click.stop="$wire.openRenameModal(openMenuId); openMenuId = null"
                class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
            >
                <x-heroicon-o-pencil class="size-4 text-slate-500" />
                Rename
            </button>

            <button
                type="button"
                @click.stop="$wire.clone(openMenuId); openMenuId = null"
                class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
            >
                <x-heroicon-o-document-duplicate class="size-4 text-slate-500" />
                Duplicate
            </button>
        </div>
    </template>
</div>
