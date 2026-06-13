{{-- resources/views/livewire/app/campaigns/edit.blade.php --}}
<div x-data="{ tab: @entangle('activeTab') }" class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <a href="{{ route('app.campaigns.show', $campaign) }}" wire:navigate class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700">
                    <x-heroicon-o-arrow-left class="size-4 mr-1" />
                    Back
                </a>
            </div>
            <h1 class="mt-3 text-3xl font-bold tracking-tight text-slate-900">Edit Campaign</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $campaign->title }}</p>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button href="{{ route('app.campaigns.show', $campaign) }}" variant="secondary">View Campaign</x-ui.button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button type="button"
                @click="tab = 'settings'"
                :class="tab === 'settings' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Settings
            </button>
            <button type="button"
                @click="tab = 'checkout'"
                :class="tab === 'checkout' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Checkout Modal
            </button>
            <button type="button"
                @click="tab = 'embed'"
                :class="tab === 'embed' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Embed & Share
            </button>
            <button type="button"
                @click="tab = 'actions'"
                :class="tab === 'actions' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Actions
            </button>
        </nav>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Settings Tab --}}
        <div x-show="tab === 'settings'" x-cloak class="space-y-6">
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
                        <select
                            id="status"
                            wire:model="status"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        >
                            <option value="draft">Draft</option>
                            <option value="active">Active</option>
                            <option value="paused">Paused</option>
                            <option value="ended">Ended</option>
                            <option value="archived">Archived</option>
                        </select>
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
                        <div class="mt-1 flex items-center gap-4">
                            @if ($existing_image)
                                <img src="{{ Storage::disk('public')->url($existing_image) }}" alt="Current campaign image" class="h-20 w-20 rounded-lg object-cover border border-slate-200" />
                            @else
                                <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                    <x-heroicon-o-photo class="size-8" />
                                </div>
                            @endif
                            <div class="flex-1">
                                <input
                                    type="file"
                                    id="image"
                                    wire:model="image"
                                    accept="image/*"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100"
                                />
                                @if ($existing_image)
                                    <button type="button" wire:click="removeImage" class="mt-2 text-xs text-red-600 hover:text-red-800">Remove image</button>
                                @endif
                            </div>
                        </div>
                        @error('image') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror

                        @if ($image)
                            <div class="mt-3">
                                <p class="text-xs text-slate-500 mb-1">Preview:</p>
                                <img src="{{ $image->temporaryUrl() }}" class="h-32 w-auto rounded-lg object-cover" alt="Preview" />
                            </div>
                        @endif
                    </div>
                </div>
            </x-ui.card>

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
                            wire:click="$toggle('has_target')"
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
                            wire:click="$toggle('has_end_date')"
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

            <x-ui.card title="Post-Donation">
                <div class="space-y-4">
                    <div>
                        <label for="thank_you_message" class="block text-sm font-medium text-slate-700">Thank You Message</label>
                        <textarea
                            id="thank_you_message"
                            wire:model="thank_you_message"
                            rows="3"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            placeholder="Shown to donors after they complete a donation..."
                        ></textarea>
                        @error('thank_you_message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="redirect_url" class="block text-sm font-medium text-slate-700">Redirect URL</label>
                        <input
                            type="url"
                            id="redirect_url"
                            wire:model="redirect_url"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            placeholder="https://example.com/thank-you"
                        />
                        @error('redirect_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-ui.card>

            <div class="flex items-center justify-end gap-3">
                <x-ui.button href="{{ route('app.campaigns.show', $campaign) }}" variant="ghost">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
            </div>
        </div>

        {{-- Checkout Modal Tab --}}
        <div x-show="tab === 'checkout'" x-cloak class="space-y-6">
            <x-ui.card title="Donation Settings">
                <div class="space-y-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-sm font-medium text-slate-900">Allow Recurring Donations</h3>
                            <p class="text-xs text-slate-500">Let donors set up monthly subscriptions</p>
                        </div>
                        <button
                            type="button"
                            wire:click="$toggle('allow_recurring')"
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
                            wire:click="$toggle('allow_custom_amount')"
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

            <x-ui.card title="Defaults">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="default_frequency" class="block text-sm font-medium text-slate-700">Default Frequency</label>
                        <select
                            id="default_frequency"
                            wire:model="default_frequency"
                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        >
                            <option value="one_time">One-time</option>
                            <option value="monthly">Monthly</option>
                        </select>
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

            <div class="flex items-center justify-end gap-3">
                <x-ui.button href="{{ route('app.campaigns.show', $campaign) }}" variant="ghost">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
            </div>
        </div>
    </form>

    {{-- Embed & Share Tab --}}
    <div x-show="tab === 'embed'" x-cloak class="space-y-6">
        @php
            $campaignUrl = route('donations.campaign-show', $campaign);
            $whatsappUrl = 'https://wa.me/?text='.urlencode('Support our campaign: '.$campaignUrl);
            $embedButton = '<a href="'.$campaignUrl.'" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-6 py-3 text-sm font-semibold text-white hover:bg-teal-700" target="_blank">Donate Now</a>';
            $embedIframe = '<iframe src="'.$campaignUrl.'" width="100%" height="600" frameborder="0" style="border-radius: 0.75rem;"></iframe>';
        @endphp

        <x-ui.card title="Share">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Campaign Page URL</label>
                    <div class="mt-1 flex items-center gap-2">
                        <input type="text" readonly value="{{ $campaignUrl }}" class="block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-600" />
                        <x-ui.copy-button value="{{ $campaignUrl }}" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">WhatsApp Share</label>
                    <a href="{{ $whatsappUrl }}" target="_blank" class="mt-1 inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <x-heroicon-o-share class="size-4" />
                        Share on WhatsApp
                    </a>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">QR Code</label>
                    <div class="mt-2 inline-block rounded-lg border border-slate-200 p-2">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($campaignUrl) }}" alt="QR Code" class="h-40 w-40" />
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="Embed Code">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700">Button HTML</label>
                    <div class="mt-1 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-2">
                            <span class="text-xs font-medium text-slate-500">Copy to clipboard</span>
                            <x-ui.copy-button value="{{ $embedButton }}" />
                        </div>
                        <pre class="overflow-x-auto p-4 text-xs leading-relaxed text-slate-700"><code>{{ $embedButton }}</code></pre>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Iframe Embed</label>
                    <div class="mt-1 overflow-hidden rounded-lg border border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between gap-3 border-b border-slate-200 px-4 py-2">
                            <span class="text-xs font-medium text-slate-500">Copy to clipboard</span>
                            <x-ui.copy-button value="{{ $embedIframe }}" />
                        </div>
                        <pre class="overflow-x-auto p-4 text-xs leading-relaxed text-slate-700"><code>{{ $embedIframe }}</code></pre>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Actions Tab --}}
    <div x-show="tab === 'actions'" x-cloak class="space-y-6">
        <x-ui.card title="Danger Zone">
            <div class="space-y-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">Archive Campaign</h3>
                        <p class="text-xs text-slate-500">Stop accepting donations and mark the campaign as ended. This can be reversed by changing the status back to Active.</p>
                    </div>
                    <x-ui.button wireClick="archive" variant="danger" onclick="return confirm('Are you sure you want to archive this campaign?')">Archive</x-ui.button>
                </div>

                <div class="border-t border-slate-100"></div>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">Duplicate Campaign</h3>
                        <p class="text-xs text-slate-500">Create a new campaign with the same settings. The new campaign will be a draft.</p>
                    </div>
                    <x-ui.button wireClick="duplicate" variant="secondary">Duplicate</x-ui.button>
                </div>
                <div class="border-t border-slate-100"></div>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">Delete Campaign</h3>
                        <p class="text-xs text-slate-500">Permanently delete this campaign and all associated data. This action cannot be undone.</p>
                    </div>
                    <x-ui.button wireClick="delete" variant="danger" onclick="return confirm('Are you sure you want to permanently delete this campaign? This cannot be undone.')">Delete</x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>
</div>
