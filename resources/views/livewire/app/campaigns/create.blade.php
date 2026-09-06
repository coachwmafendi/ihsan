{{-- resources/views/livewire/app/campaigns/create.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('app.campaigns.index') }}" wire:navigate class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back
        </a>
    </div>

    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Create Campaign</h1>
        <p class="mt-1 text-sm text-slate-500">Set up a new fundraising campaign for your organization</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Basic Info --}}
        <x-ui.card title="Basic Information">
            <div class="space-y-4">
                <div>
                    <label for="title" class="block text-sm font-medium text-slate-700">Campaign Title <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="title"
                        wire:model="title"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="e.g. Ramadan Fundraiser 2026"
                    />
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="status" class="block text-sm font-medium text-slate-700">Status <span class="text-red-500">*</span></label>
                    <x-ui.select id="status" wire:model="status" class="mt-1 block w-full">
                        <flux:select.option value="draft">Draft</flux:select.option>
                        <flux:select.option value="active">Active</flux:select.option>
                    </x-ui.select>
                    @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                    <textarea
                        id="description"
                        wire:model="description"
                        rows="4"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="Describe the purpose of this campaign..."
                    ></textarea>
                    @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-slate-700">Campaign Image</label>
                    <input
                        type="file"
                        id="image"
                        wire:model.live="image"
                        accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/avif"
                        class="mt-1 block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100"
                    />
                    <p class="mt-1 text-xs text-slate-500">Allowed: JPG, PNG, GIF, WebP, AVIF. Max 5 MB.</p>
                    <x-campaign-image-guidance class="mt-1" />
                    @error('image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                    @if ($image)
                        <div class="mt-3">
                            <img src="{{ $image->temporaryUrl() }}" class="h-32 w-auto rounded-lg object-cover" alt="Preview" />
                        </div>
                    @endif
                </div>
            </div>
        </x-ui.card>

        {{-- Campaign Settings --}}
        <x-ui.card title="Campaign Settings">
            <div class="space-y-6">
                {{-- Target --}}
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">Fundraising Target</h3>
                        <p class="text-xs text-slate-500">Set a goal amount for this campaign</p>
                    </div>
                    <button
                        type="button"
                        wire:click="toggleHasTarget"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 {{ $has_target ? 'bg-teal-600' : 'bg-slate-200' }}"
                        role="switch"
                        aria-checked="{{ $has_target ? 'true' : 'false' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $has_target ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                @if ($has_target)
                    <div>
                        <label for="target_amount" class="block text-sm font-medium text-slate-700">Target Amount (RM)</label>
                        <input
                            type="number"
                            step="0.01"
                            id="target_amount"
                            wire:model="target_amount"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            placeholder="10000.00"
                        />
                        @error('target_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div class="border-t border-slate-100"></div>

                {{-- End Date --}}
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">End Date</h3>
                        <p class="text-xs text-slate-500">Set an end date for this campaign</p>
                    </div>
                    <button
                        type="button"
                        wire:click="toggleHasEndDate"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 {{ $has_end_date ? 'bg-teal-600' : 'bg-slate-200' }}"
                        role="switch"
                        aria-checked="{{ $has_end_date ? 'true' : 'false' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $has_end_date ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                @if ($has_end_date)
                    <div>
                        <label for="end_date" class="block text-sm font-medium text-slate-700">End Date</label>
                        <input
                            type="date"
                            id="end_date"
                            wire:model="end_date"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                        @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        </x-ui.card>

        {{-- Donation Settings --}}
        <x-ui.card title="Donation Settings">
            <div class="space-y-6">
                <div>
                    <label for="payment_gateway" class="block text-sm font-medium text-slate-700">Payment Gateway</label>
                    <x-ui.select id="payment_gateway" wire:model="payment_gateway" class="mt-1 block w-full">
                        <flux:select.option value="stripe">Stripe</flux:select.option>
                        @if (config('services.chip.donations_enabled') || $payment_gateway === 'chip')
                            <flux:select.option value="chip">CHIP</flux:select.option>
                        @endif
                    </x-ui.select>
                    @error('payment_gateway') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="border-t border-slate-100"></div>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">Allow Recurring Donations</h3>
                        <p class="text-xs text-slate-500">Let donors set up monthly subscriptions</p>
                    </div>
                    <button
                        type="button"
                        wire:click="toggleAllowRecurring"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 {{ $allow_recurring ? 'bg-teal-600' : 'bg-slate-200' }}"
                        role="switch"
                        aria-checked="{{ $allow_recurring ? 'true' : 'false' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $allow_recurring ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <div class="border-t border-slate-100"></div>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">Allow Custom Amount</h3>
                        <p class="text-xs text-slate-500">Donors can enter any amount</p>
                    </div>
                    <button
                        type="button"
                        wire:click="toggleAllowCustomAmount"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 {{ $allow_custom_amount ? 'bg-teal-600' : 'bg-slate-200' }}"
                        role="switch"
                        aria-checked="{{ $allow_custom_amount ? 'true' : 'false' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $allow_custom_amount ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>

                <div>
                    <label for="minimum_amount" class="block text-sm font-medium text-slate-700">Minimum Amount (RM)</label>
                    <input
                        type="number"
                        step="0.01"
                        id="minimum_amount"
                        wire:model="minimum_amount"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="5.00"
                    />
                    @error('minimum_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </x-ui.card>

        {{-- Suggested Amounts --}}
        <x-ui.card title="Suggested Amounts" description="Preset donation amounts shown to donors">
            <div class="space-y-3">
                @foreach ($suggested_amounts as $index => $amount)
                    <div class="flex items-start gap-3">
                        <div class="flex-1">
                            <input
                                type="number"
                                step="0.01"
                                wire:model="suggested_amounts.{{ $index }}.value"
                                placeholder="Amount (RM)"
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            />
                        </div>
                        <div class="flex-1">
                            <input
                                type="text"
                                wire:model="suggested_amounts.{{ $index }}.label"
                                placeholder="Label (optional)"
                                class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            />
                        </div>
                        <button
                            type="button"
                            wire:click="removeSuggestedAmount({{ $index }})"
                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white p-2 text-slate-400 hover:bg-slate-50 hover:text-red-600"
                        >
                            <x-heroicon-o-trash class="size-4" />
                        </button>
                    </div>
                @endforeach

                <button
                    type="button"
                    wire:click="addSuggestedAmount"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                >
                    <x-heroicon-o-plus class="size-4" />
                    Add Amount
                </button>
            </div>
        </x-ui.card>

        {{-- Defaults --}}
        <x-ui.card title="Defaults">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label for="default_frequency" class="block text-sm font-medium text-slate-700">Default Frequency</label>
                    <x-ui.select id="default_frequency" wire:model="default_frequency" class="mt-1 block w-full">
                        <flux:select.option value="one_time">One-time</flux:select.option>
                        <flux:select.option value="monthly">Monthly</flux:select.option>
                    </x-ui.select>
                </div>

                <div>
                    <label for="default_amount" class="block text-sm font-medium text-slate-700">Default Amount (RM)</label>
                    <input
                        type="number"
                        step="0.01"
                        id="default_amount"
                        wire:model="default_amount"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="50.00"
                    />
                </div>
            </div>
        </x-ui.card>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
            <x-ui.button type="submit" variant="primary">Create Campaign</x-ui.button>
        </div>
    </form>
</div>
