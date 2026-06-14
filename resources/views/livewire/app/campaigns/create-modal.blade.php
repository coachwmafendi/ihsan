{{-- resources/views/livewire/app/campaigns/create-modal.blade.php --}}
<div>
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
                            <x-ui.select wire:model.live="cloneCampaignId" class="w-full">
                                <flux:select.option value="">Select a campaign</flux:select.option>
                                @foreach ($this->cloneableCampaigns as $campaignOption)
                                    <flux:select.option value="{{ $campaignOption->id }}">{{ $campaignOption->title }}</flux:select.option>
                                @endforeach
                            </x-ui.select>
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
