{{-- resources/views/livewire/app/campaigns/index.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Campaigns</h1>
            <p class="mt-1 text-sm text-slate-500">Manage your fundraising campaigns</p>
        </div>
        <x-ui.button wire:click="openCreateModal" variant="primary">
            <x-heroicon-o-plus class="size-4" />
            Create Campaign
        </x-ui.button>
    </div>

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

        <select
            wire:model.live="statusFilter"
            class="h-10 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
        >
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="draft">Draft</option>
            <option value="paused">Paused</option>
            <option value="ended">Ended</option>
        </select>
    </div>

    {{-- Campaigns Table --}}
    <x-ui.card>
        @if ($this->campaigns->isNotEmpty())
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead>
                        <tr class="bg-slate-50">
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
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
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">
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
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Raised</th>
                            <th scope="col" class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Donations</th>
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
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach ($this->campaigns as $campaign)
                            <tr
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
                                            @if ($campaign->has_target && $campaign->target_amount)
                                                <div class="mt-0.5 flex items-center gap-2 text-xs text-slate-500">
                                                    <div class="h-1 w-16 rounded-full bg-slate-100">
                                                        @php
                                                            $pct = $campaign->target_amount > 0
                                                                ? min(100, ($campaign->collected_amount / $campaign->target_amount) * 100)
                                                                : 0;
                                                        @endphp
                                                        <div class="h-1 rounded-full bg-teal-600" style="width: {{ $pct }}%"></div>
                                                    </div>
                                                    <span>{{ number_format($pct, 0) }}%</span>
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
                                    {{ $campaign->created_at->format('M d, Y') }}
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
            <x-ui.empty-state
                icon="heroicon-o-megaphone"
                title="No campaigns found"
                description="Get started by creating your first fundraising campaign."
                action-label="Create Campaign"
                action-url="{{ route('app.campaigns.create') }}"
            />
        @endif
    </x-ui.card>

    {{-- Create Campaign Modal --}}
    @if ($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-start justify-center pt-24 sm:items-center sm:pt-0" role="dialog" aria-modal="true">
            {{-- Backdrop --}}
            <div
                class="fixed inset-0 bg-black/50 transition-opacity"
                wire:click="closeCreateModal"
            ></div>

            {{-- Modal Panel --}}
            <div class="relative w-full max-w-lg rounded-xl bg-white shadow-xl mx-4">
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Create a new campaign</h2>
                    <button
                        wire:click="closeCreateModal"
                        class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    >
                        <x-heroicon-o-x-mark class="size-5" />
                    </button>
                </div>

                {{-- Body --}}
                <div class="px-6 py-6 space-y-6">
                    <p class="text-sm text-slate-600">
                        Clone an existing campaign or create a new campaign with your default settings.
                    </p>

                    {{-- Radio Options --}}
                    <div class="space-y-3">
                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 transition-colors hover:bg-slate-50 has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50">
                            <input
                                type="radio"
                                wire:model.live="createMode"
                                value="new"
                                class="mt-0.5 size-4 border-slate-300 text-teal-600 focus:ring-teal-500"
                            />
                            <div>
                                <span class="block text-sm font-medium text-slate-900">New campaign with default settings</span>
                                <span class="block text-xs text-slate-500 mt-0.5">Start fresh with default settings</span>
                            </div>
                        </label>

                        <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 p-3 transition-colors hover:bg-slate-50 has-[:checked]:border-teal-500 has-[:checked]:bg-teal-50">
                            <input
                                type="radio"
                                wire:model.live="createMode"
                                value="clone"
                                class="mt-0.5 size-4 border-slate-300 text-teal-600 focus:ring-teal-500"
                            />
                            <div>
                                <span class="block text-sm font-medium text-slate-900">Clone an existing campaign</span>
                                <span class="block text-xs text-slate-500 mt-0.5">Copy settings from an existing campaign</span>
                            </div>
                        </label>
                    </div>

                    <hr class="border-slate-200" />

                    {{-- Clone Campaign Select --}}
                    @if ($createMode === 'clone')
                        <div class="space-y-2">
                            <label class="block text-sm font-medium text-slate-700">
                                Campaign to clone
                            </label>
                            <select
                                wire:model.live="cloneCampaignId"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            >
                                <option value="">Select a campaign</option>
                                @foreach ($this->cloneableCampaigns as $campaignOption)
                                    <option value="{{ $campaignOption->id }}">{{ $campaignOption->title }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Campaign Name --}}
                    <div class="space-y-2">
                        <label for="newCampaignName" class="block text-sm font-medium text-slate-700">
                            Name
                        </label>
                        <input
                            id="newCampaignName"
                            type="text"
                            wire:model="newCampaignName"
                            placeholder="My awesome campaign"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <x-ui.button wire:click="closeCreateModal" variant="secondary">
                        Cancel
                    </x-ui.button>
                    <x-ui.button wire:click="createCampaign" variant="primary">
                        Create campaign
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
