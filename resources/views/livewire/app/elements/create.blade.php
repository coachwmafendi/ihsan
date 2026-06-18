{{-- resources/views/livewire/app/elements/create.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('app.elements.index') }}" wire:navigate class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back
        </a>
    </div>

    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Create Element</h1>
        <p class="mt-1 text-sm text-slate-500">Add a new embeddable element to your campaign</p>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Basic Info --}}
        <x-ui.card title="Basic Information">
            <div class="space-y-4">
                <div>
                    <label for="campaign_id" class="block text-sm font-medium text-slate-700">Campaign <span class="text-red-500">*</span></label>
                    <x-ui.select id="campaign_id" wire:model="campaign_id" class="mt-1 block w-full">
                        <flux:select.option value="">Select a campaign</flux:select.option>
                        @foreach ($this->campaigns as $campaign)
                            <flux:select.option value="{{ $campaign->id }}">{{ $campaign->title }}</flux:select.option>
                        @endforeach
                    </x-ui.select>
                    @error('campaign_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="type" class="block text-sm font-medium text-slate-700">Type <span class="text-red-500">*</span></label>
                    <x-ui.select id="type" wire:model="type" class="mt-1 block w-full">
                        <flux:select.option value="button">Button</flux:select.option>
                        <flux:select.option value="floating_button">Floating Button</flux:select.option>
                        <flux:select.option value="form">Form</flux:select.option>
                        <flux:select.option value="popup">Popup</flux:select.option>
                        <flux:select.option value="link">Link</flux:select.option>
                        <flux:select.option value="sticky_button">Sticky Button</flux:select.option>
                        <flux:select.option value="qr_code">QR Code</flux:select.option>
                    </x-ui.select>
                    @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700">Name <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="e.g. Ramadan Donation Button"
                    />
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">Active</h3>
                        <p class="text-xs text-slate-500">Make this element visible and usable</p>
                    </div>
                    <button
                        type="button"
                        wire:click="toggleIsActive"
                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 {{ $is_active ? 'bg-teal-600' : 'bg-slate-200' }}"
                        role="switch"
                        aria-checked="{{ $is_active ? 'true' : 'false' }}"
                    >
                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                    </button>
                </div>
            </div>
        </x-ui.card>

        {{-- Configuration --}}
        <x-ui.card title="Configuration" description="Optional display settings">
            <div class="space-y-4">
                <div>
                    <label for="config_title" class="block text-sm font-medium text-slate-700">Title</label>
                    <input
                        type="text"
                        id="config_title"
                        wire:model="config_title"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="e.g. Support Our Cause"
                    />
                </div>

                <div>
                    <label for="config_message" class="block text-sm font-medium text-slate-700">Message</label>
                    <textarea
                        id="config_message"
                        wire:model="config_message"
                        maxlength="100"
                        rows="3"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="Enter a message to display..."
                    ></textarea>
                </div>

                <div>
                    <label for="config_button_text" class="block text-sm font-medium text-slate-700">Button Text</label>
                    <input
                        type="text"
                        id="config_button_text"
                        wire:model="config_button_text"
                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        placeholder="e.g. Donate Now"
                    />
                </div>

                @if ($type === 'popup')
                    <div class="space-y-4 border-t border-slate-200 pt-4">
                        <div>
                            <label for="config_action" class="block text-sm font-medium text-slate-700">Action</label>
                            <select
                                id="config_action"
                                wire:model="config_action"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            >
                                <option value="checkout_modal">Open checkout modal</option>
                                <option value="open_campaign_page">Open campaign page</option>
                            </select>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="config_trigger" class="block text-sm font-medium text-slate-700">Trigger</label>
                                <select
                                    id="config_trigger"
                                    wire:model="config_trigger"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                >
                                    <option value="after_delay">After delay</option>
                                    <option value="immediately">Immediately</option>
                                    <option value="on_scroll">On scroll</option>
                                    <option value="on_exit">On exit intent</option>
                                </select>
                            </div>

                            <div>
                                <label for="config_delay" class="block text-sm font-medium text-slate-700">Delay (seconds)</label>
                                <input
                                    type="number"
                                    id="config_delay"
                                    wire:model="config_delay"
                                    min="0"
                                    max="3600"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                />
                            </div>

                            <div>
                                <label for="config_frequency" class="block text-sm font-medium text-slate-700">Frequency</label>
                                <select
                                    id="config_frequency"
                                    wire:model="config_frequency"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                >
                                    <option value="once">Once</option>
                                    <option value="once_per_day">Once per day</option>
                                    <option value="once_per_session">Once per session</option>
                                    <option value="once_per_week">Once per week</option>
                                    <option value="once_per_month">Once per month</option>
                                </select>
                            </div>

                            <div>
                                <label for="config_visibility" class="block text-sm font-medium text-slate-700">Visibility</label>
                                <select
                                    id="config_visibility"
                                    wire:model="config_visibility"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                >
                                    <option value="desktop_mobile">Desktop & mobile</option>
                                    <option value="desktop_only">Desktop only</option>
                                    <option value="mobile_only">Mobile only</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="config_layout" class="block text-sm font-medium text-slate-700">Layout</label>
                                <select
                                    id="config_layout"
                                    wire:model="config_layout"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                >
                                    <option value="simple">Simple</option>
                                    <option value="full">Full banner</option>
                                </select>
                            </div>

                            <div>
                                <label for="config_color" class="block text-sm font-medium text-slate-700">Colour</label>
                                <select
                                    id="config_color"
                                    wire:model="config_color"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                >
                                    <option value="campaign">Campaign (green)</option>
                                    <option value="blue">Blue</option>
                                    <option value="teal">Teal</option>
                                    <option value="green">Green</option>
                                    <option value="orange">Orange</option>
                                    <option value="red">Red</option>
                                    <option value="purple">Purple</option>
                                    <option value="dark">Dark</option>
                                </select>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="config_image_url" class="block text-sm font-medium text-slate-700">Image URL</label>
                                <input
                                    type="text"
                                    id="config_image_url"
                                    wire:model="config_image_url"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="https://..."
                                />
                                @error('config_image_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label for="config_button_effect" class="block text-sm font-medium text-slate-700">Button Effect</label>
                                <select
                                    id="config_button_effect"
                                    wire:model="config_button_effect"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                >
                                    <option value="none">None (solid colour)</option>
                                    <option value="gradient_teal_green">Gradient — Teal &amp; Green</option>
                                    <option value="gradient_blue_purple">Gradient — Blue &amp; Purple</option>
                                    <option value="gradient_orange_red">Gradient — Orange &amp; Red</option>
                                    <option value="gradient_rose_pink">Gradient — Rose &amp; Pink</option>
                                    <option value="gradient_amber_orange">Gradient — Amber &amp; Orange</option>
                                    <option value="gradient_cyan_blue">Gradient — Cyan &amp; Blue</option>
                                    <option value="gradient_emerald_teal">Gradient — Emerald &amp; Teal</option>
                                    <option value="gradient_indigo_purple">Gradient — Indigo &amp; Purple</option>
                                    <option value="gradient_gold_amber">Gradient — Gold &amp; Amber</option>
                                    <option value="gradient_pink_purple">Gradient — Pink &amp; Purple</option>
                                </select>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </x-ui.card>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <x-ui.button href="{{ route('app.elements.index') }}" variant="ghost">Cancel</x-ui.button>
            <x-ui.button type="submit" variant="primary">Create Element</x-ui.button>
        </div>
    </form>
</div>
