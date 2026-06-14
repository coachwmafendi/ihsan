{{-- resources/views/livewire/app/elements/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Elements</h1>
            <p class="mt-1 text-sm text-slate-500">Manage donation forms, buttons, and embeddable elements</p>
        </div>
        <x-ui.button wire:click="openCreateModal" variant="primary">
            <x-heroicon-o-plus class="size-4" />
            Create Element
        </x-ui.button>
    </div>

    {{-- Summary Stats --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-ui.stat-card label="Total Elements" value="{{ number_format($this->totalCount) }}" />
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

        <select
            wire:model.live="typeFilter"
            class="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
        >
            <option value="">All Types</option>
            <option value="button">Button</option>
            <option value="floating_button">Floating Button</option>
            <option value="form">Form</option>
            <option value="popup">Popup</option>
        </select>
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
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Embed Code</th>
                            <th scope="col" class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($this->elements as $element)
                            <tr class="transition-colors hover:bg-slate-50">
                                <td class="px-5 py-4">
                                    <p class="text-sm font-medium text-slate-900">{{ $element->name }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $element->token }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 capitalize">
                                        {{ str_replace('_', ' ', $element->type->value) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    @if ($element->campaign)
                                        <a href="{{ route('app.campaigns.edit', $element->campaign) }}" wire:navigate.stop class="hover:text-teal-600">
                                            {{ $element->campaign->title }}
                                        </a>
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
                                    {{ $element->created_at->format('M d, Y') }}
                                </td>
                                <td class="px-5 py-4">
                                    <div x-data="{ copied: false }" class="flex items-center gap-2">
                                        <code class="max-w-xs truncate rounded bg-slate-100 px-2 py-1 text-xs font-mono text-slate-600">{{ $element->token }}</code>
                                        <button
                                            type="button"
                                            @click="navigator.clipboard.writeText(`<script src='{{ url('/e/widget.js') }}' data-token='{{ $element->token }}' data-type='{{ $element->type->value }}' async><\/script>`); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="shrink-0 rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                            title="Copy embed code"
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
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('app.elements.edit', $element) }}" wire:navigate class="text-sm font-medium text-teal-600 hover:text-teal-700">Edit</a>
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
                icon="heroicon-o-code-bracket"
                title="No elements found"
                description="Try adjusting your search or create a new element."
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
                        <select id="newType" wire:model="newType" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                            <option value="button">Button</option>
                            <option value="floating_button">Floating Button</option>
                            <option value="form">Form</option>
                            <option value="popup">Popup</option>
                            <option value="link">Link</option>
                            <option value="sticky_button">Sticky Button</option>
                        </select>
                        @error('newType') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="newName" class="block text-sm font-medium text-slate-700">Element Name</label>
                        <input type="text" id="newName" wire:model="newName" placeholder="e.g. Homepage Donate Button" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                        @error('newName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="newCampaignId" class="block text-sm font-medium text-slate-700">Open Campaign</label>
                        <select id="newCampaignId" wire:model="newCampaignId" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                            <option value="">Select a campaign</option>
                            @foreach ($this->campaigns as $campaign)
                                <option value="{{ $campaign->id }}">{{ $campaign->title }}</option>
                            @endforeach
                        </select>
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
</div>
