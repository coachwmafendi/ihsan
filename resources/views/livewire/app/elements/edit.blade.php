{{-- resources/views/livewire/app/elements/edit.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex items-center gap-3">
        <a href="{{ route('app.elements.index') }}" wire:navigate class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700">
            <x-heroicon-o-arrow-left class="mr-1 size-4" />
            Back
        </a>
    </div>

    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">Edit Element</h1>
        <p class="mt-1 text-sm text-slate-500">{{ $element->name }} · Type {{ ucwords(str_replace('_', ' ', $element->type->value)) }}</p>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left column: Form fields --}}
            <div class="space-y-6 lg:col-span-2">
                @if ($element->type->value === 'button')
                    {{-- Embed Code --}}
                    <x-ui.card title="Embed Code" description="Copy this code to embed on your website">
                        <div class="space-y-3">
                            <div class="relative">
                                <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-300"><code>&lt;script src="{{ url('/e/widget.js') }}" data-token="{{ $element->token }}" data-type="{{ $element->type->value }}" async&gt;&lt;/script&gt;</code></pre>
                                <button
                                    type="button"
                                    x-data="{ copied: false }"
                                    @click="navigator.clipboard.writeText(`<script src='{{ url('/e/widget.js') }}' data-token='{{ $element->token }}' data-type='{{ $element->type->value }}' async></script>`); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="absolute right-2 top-2 rounded-md bg-slate-700 px-2 py-1 text-xs text-white hover:bg-slate-600"
                                >
                                    <span x-show="!copied">Copy</span>
                                    <span x-show="copied" x-cloak>Copied!</span>
                                </button>
                            </div>
                            <p class="text-xs text-slate-500">Token: {{ $element->token }}</p>
                        </div>
                    </x-ui.card>
                @endif

                {{-- Basic Info --}}
                <x-ui.card title="Basic Information">
                    <div class="space-y-4">
                        <div>
                            <label for="campaign_id" class="block text-sm font-medium text-slate-700">Campaign <span class="text-red-500">*</span></label>
                            <select
                                id="campaign_id"
                                wire:model="campaign_id"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            >
                                <option value="">Select a campaign</option>
                                @foreach ($this->campaigns as $campaign)
                                    <option value="{{ $campaign->id }}">{{ $campaign->title }}</option>
                                @endforeach
                            </select>
                            @error('campaign_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700">Type</label>
                            <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                                {{ ucwords(str_replace('_', ' ', $element->type->value)) }}
                            </div>
                            <p class="mt-1 text-xs text-slate-500">Type cannot be changed after creation.</p>
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
                                wire:click="$toggle('is_active')"
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
                <x-ui.card title="Configuration">
                    <div class="space-y-4">
                        @if ($element->type->value !== 'button')
                            <div>
                                <label for="config_title" class="block text-sm font-medium text-slate-700">Title</label>
                                <input
                                    type="text"
                                    id="config_title"
                                    wire:model.live="config_title"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="e.g. Support our cause"
                                />
                            </div>

                            <div>
                                <label for="config_message" class="block text-sm font-medium text-slate-700">Message</label>
                                <textarea
                                    id="config_message"
                                    wire:model.live="config_message"
                                    rows="3"
                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="Short description shown to donors..."
                                ></textarea>
                            </div>
                        @endif

                        <div>
                            <label for="config_button_text" class="block text-sm font-medium text-slate-700">Button Text</label>
                            <input
                                type="text"
                                id="config_button_text"
                                wire:model.live="config_button_text"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="e.g. Donate Now"
                            />
                        </div>

                        @if ($element->type->value === 'button')
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="config_button_effect" class="block text-sm font-medium text-slate-700">Button Effect</label>
                                    <select id="config_button_effect" wire:model.live="config_button_effect" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
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

                                <div>
                                    <label for="config_button_color" class="block text-sm font-medium text-slate-700">Button Colour</label>
                                    <select id="config_button_color" wire:model.live="config_button_color" {{ $config_button_effect !== 'none' ? 'disabled' : '' }} class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 {{ $config_button_effect !== 'none' ? 'bg-slate-100 text-slate-500 cursor-not-allowed' : '' }}">
                                        <option value="bg-blue-600 hover:bg-blue-700">Blue</option>
                                        <option value="bg-teal-600 hover:bg-teal-700">Teal</option>
                                        <option value="bg-green-600 hover:bg-green-700">Green</option>
                                        <option value="bg-orange-600 hover:bg-orange-700">Orange</option>
                                        <option value="bg-red-600 hover:bg-red-700">Red</option>
                                        <option value="bg-purple-600 hover:bg-purple-700">Purple</option>
                                        <option value="bg-gray-900 hover:bg-gray-800">Dark</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="config_button_size" class="block text-sm font-medium text-slate-700">Button Size</label>
                                    <select id="config_button_size" wire:model.live="config_button_size" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                        <option value="text-sm px-4 py-2">Small</option>
                                        <option value="text-base px-6 py-3">Medium</option>
                                        <option value="text-lg px-8 py-4">Large</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="config_corner_radius" class="block text-sm font-medium text-slate-700">Corner Radius (px)</label>
                                    <input type="number" min="0" max="100" id="config_corner_radius" wire:model.live="config_corner_radius" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" />
                                </div>

                                <div>
                                    <label for="config_button_icon" class="block text-sm font-medium text-slate-700">Icon</label>
                                    <select id="config_button_icon" wire:model.live="config_button_icon" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                        <option value="none">No icon</option>
                                        <option value="heart">Heart</option>
                                        <option value="hand">Hand</option>
                                        <option value="star">Star</option>
                                        <option value="gift">Gift</option>
                                        <option value="plus">Plus</option>
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>
                </x-ui.card>

                @if ($element->type->value !== 'button')
                    {{-- Embed Code --}}
                    <x-ui.card title="Embed Code" description="Copy this code to embed on your website">
                        <div class="space-y-3">
                            <div class="relative">
                                <pre class="overflow-x-auto rounded-lg bg-slate-900 p-4 text-xs text-slate-300"><code>&lt;script src="{{ url('/e/widget.js') }}" data-token="{{ $element->token }}" data-type="{{ $element->type->value }}" async&gt;&lt;/script&gt;</code></pre>
                                <button
                                    type="button"
                                    x-data="{ copied: false }"
                                    @click="navigator.clipboard.writeText(`<script src='{{ url('/e/widget.js') }}' data-token='{{ $element->token }}' data-type='{{ $element->type->value }}' async></script>`); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="absolute right-2 top-2 rounded-md bg-slate-700 px-2 py-1 text-xs text-white hover:bg-slate-600"
                                >
                                    <span x-show="!copied">Copy</span>
                                    <span x-show="copied" x-cloak>Copied!</span>
                                </button>
                            </div>
                            <p class="text-xs text-slate-500">Token: {{ $element->token }}</p>
                        </div>
                    </x-ui.card>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-between">
                    <x-ui.button wireClick="delete" variant="danger" onclick="return confirm('Are you sure you want to delete this element?')">Delete Element</x-ui.button>
                    <div class="flex items-center gap-3">
                        <x-ui.button href="{{ route('app.elements.index') }}" variant="ghost">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
                    </div>
                </div>
            </div>

            {{-- Right column: Preview --}}
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-6">
                    {{-- @php
                        $previewConfig = array_merge($element->config ?? [], array_filter([
                            'title' => $config_title,
                            'message' => $config_message,
                            'button_text' => $config_button_text,
                        ]));
                    @endphp --}}
                    <x-element-preview
                        :type="$element->type"
                        :config="array_merge($element->config ?? [], array_filter([
                            'title' => $config_title,
                            'message' => $config_message,
                            'button_text' => $config_button_text,
                            'button_color' => $config_button_color,
                            'button_size' => $config_button_size,
                            'corner_radius' => $config_corner_radius,
                            'button_icon' => $config_button_icon,
                            'button_effect' => $config_button_effect,
                        ], fn ($value) => $value !== null && $value !== ''))"
                    />
                </div>
            </div>
        </div>
    </form>
</div>
