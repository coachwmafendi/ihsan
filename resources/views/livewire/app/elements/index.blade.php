{{-- resources/views/livewire/app/elements/index.blade.php --}}
<div class="space-y-6" x-data="{ openMenuId: null, menuRowId: null, menuTop: 0, menuLeft: 0, closeIfOutside: null, init() { this.closeIfOutside = (e) => { if (! e.target.closest('[data-action-menu]')) { this.openMenuId = null; } }; document.addEventListener('click', this.closeIfOutside); }, destroy() { document.removeEventListener('click', this.closeIfOutside); }, openMenu(id, el, rowId = null) { const r = el.getBoundingClientRect(); this.menuTop = r.bottom + 4; this.menuLeft = r.right - 208; this.menuRowId = rowId; this.openMenuId = (this.openMenuId === id ? null : id); } }">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">
                {{ $showArchived ? 'Archived Elements' : 'Elements' }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $showArchived ? 'Restore elements to make them active again.' : 'Manage donation forms, buttons, and embeddable elements' }}
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
                    Create Element
                </x-ui.button>
            @endif
        </div>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card
            label="{{ $showArchived ? 'Archived Elements' : 'Total Elements' }}"
            value="{{ number_format($this->totalCount) }}"
        />
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
            <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search by name..."
                class="h-10 w-64 rounded-lg border border-slate-300 bg-white pl-9 pr-4 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            />
        </div>

        <div class="w-40">
            <x-ui.select wire:model.live="typeFilter" class="h-10">
                <flux:select.option value="">All Types</flux:select.option>
                <flux:select.option value="button">Button</flux:select.option>
                <flux:select.option value="floating_button">Floating Button</flux:select.option>
                <flux:select.option value="sticky_button">Sticky Button</flux:select.option>
                <flux:select.option value="form">Form</flux:select.option>
                <flux:select.option value="popup">Popup</flux:select.option>
                <flux:select.option value="qr_code">QR Code</flux:select.option>
                <flux:select.option value="link">Link</flux:select.option>
            </x-ui.select>
        </div>

        <div class="w-40">
            <x-ui.select wire:model.live="statusFilter" class="h-10">
                <flux:select.option value="">All Statuses</flux:select.option>
                <flux:select.option value="active">Active</flux:select.option>
                <flux:select.option value="inactive">Inactive</flux:select.option>
            </x-ui.select>
        </div>
    </div>

    {{-- Elements Table --}}
    <x-ui.card>
        @if ($this->elements->isNotEmpty())
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
                                <button wire:click="sortBy('type')" class="group inline-flex items-center gap-1">
                                    Type
                                    @if ($sortField === 'type')
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
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
                                <button wire:click="sortBy('is_active')" class="group inline-flex items-center gap-1">
                                    Status
                                    @if ($sortField === 'is_active')
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
                            @if (! $showArchived)
                                <th scope="col" class="px-5 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Embed Code</th>
                            @endif
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($this->elements as $element)
                            <tr wire:key="element-row-{{ $element->id }}" class="cursor-pointer transition-colors hover:bg-slate-50" wire:click="edit({{ $element->id }})">
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $element->name }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $element->token }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 capitalize">
                                        {{ $element->is_donor_portal_default ? 'Donor Portal Button' : str_replace('_', ' ', $element->type->value) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    @if ($element->campaign)
                                        <div class="flex flex-col gap-1">
                                            <a href="{{ route('app.campaigns.edit', $element->campaign) }}" wire:navigate.stop onclick="event.stopPropagation()" class="hover:text-teal-600">
                                                {{ $element->campaign->title }}
                                            </a>
                                            @if ($element->campaign->payment_gateway)
                                                <span class="inline-flex w-fit items-center rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600">
                                                    {{ $element->campaign->payment_gateway->getLabel() }}
                                                </span>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-slate-400">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4">
                                    @if ($element->is_active)
                                        <x-ui.badge status="active" size="sm">Active</x-ui.badge>
                                    @else
                                        <x-ui.badge status="cancelled" size="sm">Inactive</x-ui.badge>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-500">
                                    {{ myrTime($element->created_at, withLabel: false, format: 'M d, Y') }}
                                </td>
                                @if (! $showArchived)
                                @php
                                    $baseUrl = url('/');
                                    $widgetSrc = \App\Support\EmbedWidget::scriptUrl();
                                    $type = $element->type->value;
                                    $cleanType = match ($type) {
                                        'floating_button' => 'floating_button',
                                        'qr_code' => 'qr_code',
                                        'link' => 'link',
                                        default => $type,
                                    };
                                    $embedCode = match ($cleanType) {
                                        'button' => \App\Support\EmbedWidget::staticButtonHtml($element)."\n".'<script src="'.$widgetSrc.'" data-token="'.$element->token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>',
                                        'link' => \App\Support\EmbedWidget::staticLinkHtml($element)."\n".'<script src="'.$widgetSrc.'" data-token="'.$element->token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>',
                                        'floating_button' => \App\Support\EmbedWidget::staticFloatingButtonPlaceholderHtml($element)."\n".'<script src="'.$widgetSrc.'" data-token="'.$element->token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>',
                                        'qr_code' => \App\Support\EmbedWidget::staticQrCodeHtml($element)."\n".'<script src="'.$widgetSrc.'" data-token="'.$element->token.'" data-api-base="'.$baseUrl.'" data-enhance="true"></script>',
                                        default => '<script src="'.$widgetSrc.'" data-token="'.$element->token.'" data-api-base="'.$baseUrl.'"></script>',
                                    };
                                @endphp
                                    <td class="px-5 py-4" onclick="event.stopPropagation()">
                                        <div x-data="{ copied: false }" class="flex items-center gap-2">
                                            <code class="max-w-xs truncate rounded bg-slate-100 px-2 py-1 text-xs font-mono text-slate-600">
                                                data-token="{{ $element->token }}"
                                            </code>
                                            <x-ui.tooltip text="Copy embed code">
                                                <button
                                                    type="button"
                                                    @click.stop="navigator.clipboard.writeText(@js($embedCode)); copied = true; setTimeout(() => copied = false, 2000)"
                                                    class="shrink-0 rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                                >
                                                    <template x-if="!copied">
                                                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                                                    </svg>
                                                </template>
                                                <template x-if="copied">
                                                    <svg class="size-4 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                                    </svg>
                                                </template>
                                            </button>
                                        </x-ui.tooltip>
                                        </div>
                                    </td>
                                @endif
                                <td class="px-5 py-4 text-right" wire:click.stop onclick="event.stopPropagation()">
                                    <div class="flex items-center justify-end gap-2">
                                        @if ($showArchived)
                                            <button
                                                wire:click="unarchive({{ $element->id }})"
                                                wire:loading.attr="disabled"
                                                type="button"
                                                class="inline-flex items-center gap-1 rounded px-2 py-1 text-sm font-medium text-teal-600 hover:bg-teal-50 hover:text-teal-700 transition-colors"
                                            >
                                                <x-heroicon-o-arrow-path class="size-3.5" />
                                                Restore
                                            </button>
                                        @else
                                                <div wire:key="element-actions-{{ $element->public_id }}" data-action-menu>
                                                    <button
                                                        type="button"
                                                        @click.stop="openMenu('{{ $element->public_id }}', $el, {{ $element->id }})"
                                                    class="inline-flex items-center justify-center rounded-lg p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                                    aria-label="Element actions"
                                                >
                                                    <x-heroicon-o-ellipsis-horizontal class="size-5" />
                                                </button>

                                            </div>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-slate-100 px-5 py-3">
                {{ $this->elements->links() }}
            </div>
        @else
            <x-ui.empty-state
                icon="heroicon-o-archive-box"
                title="{{ $showArchived ? 'No archived elements' : 'No elements found' }}"
                description="{{ $showArchived ? 'Archived elements will appear here.' : 'Try adjusting your search or create a new element.' }}"
            />
        @endif
    </x-ui.card>

    {{-- Create Element Modal --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="$set('showCreateModal', false)"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">New Element</h2>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label for="newType" class="block text-sm font-medium text-slate-700">Element Type</label>
                        <x-ui.select id="newType" wire:model="newType" class="mt-1 block w-full">
                            <flux:select.option value="button">Button</flux:select.option>
                            <flux:select.option value="floating_button">Floating Button</flux:select.option>
                            <flux:select.option value="sticky_button">Sticky Button</flux:select.option>
                            <flux:select.option value="form">Form</flux:select.option>
                            <flux:select.option value="popup">Popup</flux:select.option>
                            <flux:select.option value="qr_code">QR Code</flux:select.option>
                            <flux:select.option value="link">Link</flux:select.option>
                        </x-ui.select>
                        @error('newType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="newName" class="block text-sm font-medium text-slate-700">Element Name</label>
                        <input type="text" id="newName" wire:model="newName" placeholder="e.g. Homepage Donate Button" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('newName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="newCampaignId" class="block text-sm font-medium text-slate-700">Open Campaign</label>
                        <x-ui.select id="newCampaignId" wire:model="newCampaignId" class="mt-1 block w-full">
                            <flux:select.option value="">Select a campaign</flux:select.option>
                            @foreach ($this->campaigns as $campaign)
                                <flux:select.option value="{{ $campaign->id }}">{{ $campaign->title }}</flux:select.option>
                            @endforeach
                        </x-ui.select>
                        @error('newCampaignId') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <x-ui.button wire:click="$set('showCreateModal', false)" variant="secondary">Cancel</x-ui.button>
                    <x-ui.button wire:click="createElement" variant="primary">
                        <span wire:loading.remove wire:target="createElement">Create</span>
                        <span wire:loading wire:target="createElement">Creating...</span>
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
                @click.stop="$wire.duplicate(menuRowId); openMenuId = null"
                class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
            >
                <x-heroicon-o-document-duplicate class="size-4 text-slate-500" />
                Duplicate element
            </button>

            <button
                type="button"
                @click.stop="$wire.archive(menuRowId); openMenuId = null"
                class="flex w-full items-center gap-2.5 px-4 py-2 text-left text-sm text-slate-700 hover:bg-slate-50"
            >
                <x-heroicon-o-archive-box class="size-4 text-slate-500" />
                Archive element
            </button>
        </div>
    </template>
</div>
