{{-- resources/views/livewire/app/elements/edit.blade.php --}}
@php
    $isButtonLike = in_array($element->type->value, ['button', 'floating_button', 'sticky_button', 'link'], true);
@endphp
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
        <p class="mt-1 flex items-center gap-2 text-sm text-slate-500">
            {{ $element->name }}
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                Type: {{ ucwords(str_replace('_', ' ', $element->type->value)) }}
            </span>
        </p>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left column: Form fields --}}
            <div class="space-y-6 lg:col-span-2">
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

                {{-- Basic Info --}}
                <x-ui.card title="Basic Information">
                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Campaign <span class="text-red-500">*</span></flux:label>
                            <flux:select wire:model="campaign_id" placeholder="Select a campaign">
                                @foreach ($this->campaigns as $campaign)
                                    <flux:select.option value="{{ $campaign->id }}">{{ $campaign->title }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="campaign_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label>Name <span class="text-red-500">*</span></flux:label>
                            <flux:input wire:model="name" placeholder="e.g. Ramadan Donation Button" />
                            <flux:error name="name" />
                        </flux:field>

                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-sm font-medium text-slate-900">Active</h3>
                                <p class="text-xs text-slate-500">Make this element visible and usable</p>
                            </div>
                            <flux:switch wire:model="is_active" />
                        </div>
                    </div>
                </x-ui.card>

                {{-- Configuration --}}
                <x-ui.card title="Configuration">
                    <div class="space-y-4">
                        @if (! $isButtonLike)
                            <flux:field>
                                <flux:label>Title</flux:label>
                                <flux:input wire:model.live="config_title" placeholder="e.g. Support our cause" />
                            </flux:field>

                        <flux:field>
                            <flux:label>Message</flux:label>
                            <flux:textarea wire:model.live="config_message" maxlength="100" rows="3" placeholder="Short description shown to donors..." />
                        </flux:field>
                        @endif

                        <flux:field>
                            <flux:label>Button Text</flux:label>
                            <flux:input wire:model.live="config_button_text" placeholder="e.g. Donate Now" />
                        </flux:field>

                        @if ($element->type->value === 'popup')
                            <div class="space-y-4 border-t border-slate-200 pt-4">
                                <flux:field>
                                    <flux:label for="config_action">Action</flux:label>
                                    <flux:select id="config_action" wire:model.live="config_action">
                                        <flux:select.option value="checkout_modal">Open checkout modal</flux:select.option>
                                        <flux:select.option value="open_campaign_page">Open campaign page</flux:select.option>
                                    </flux:select>
                                </flux:field>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field>
                                        <flux:label for="config_trigger">Trigger</flux:label>
                                        <flux:select id="config_trigger" wire:model.live="config_trigger">
                                            <flux:select.option value="after_delay">After delay</flux:select.option>
                                            <flux:select.option value="immediately">Immediately</flux:select.option>
                                            <flux:select.option value="on_scroll">On scroll</flux:select.option>
                                            <flux:select.option value="on_exit">On exit intent</flux:select.option>
                                        </flux:select>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label for="config_delay">Delay (seconds)</flux:label>
                                        <flux:input id="config_delay" type="number" min="0" max="3600" wire:model.live="config_delay" />
                                    </flux:field>

                                    <flux:field>
                                        <flux:label for="config_frequency">Frequency</flux:label>
                                        <flux:select id="config_frequency" wire:model.live="config_frequency">
                                            <flux:select.option value="once">Once</flux:select.option>
                                            <flux:select.option value="once_per_day">Once per day</flux:select.option>
                                            <flux:select.option value="once_per_session">Once per session</flux:select.option>
                                            <flux:select.option value="once_per_week">Once per week</flux:select.option>
                                            <flux:select.option value="once_per_month">Once per month</flux:select.option>
                                        </flux:select>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label for="config_visibility">Visibility</flux:label>
                                        <flux:select id="config_visibility" wire:model.live="config_visibility">
                                            <flux:select.option value="desktop_mobile">Desktop & mobile</flux:select.option>
                                            <flux:select.option value="desktop_only">Desktop only</flux:select.option>
                                            <flux:select.option value="mobile_only">Mobile only</flux:select.option>
                                        </flux:select>
                                    </flux:field>
                                </div>

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:field>
                                        <flux:label for="config_layout">Layout</flux:label>
                                        <flux:select id="config_layout" wire:model.live="config_layout">
                                            <flux:select.option value="simple">Simple</flux:select.option>
                                            <flux:select.option value="full">Full banner</flux:select.option>
                                        </flux:select>
                                    </flux:field>

                                    <flux:field>
                                        <flux:label for="config_color">Colour</flux:label>
                                        <flux:select id="config_color" wire:model.live="config_color">
                                            <flux:select.option value="campaign">Campaign (green)</flux:select.option>
                                            <flux:select.option value="blue">Blue</flux:select.option>
                                            <flux:select.option value="teal">Teal</flux:select.option>
                                            <flux:select.option value="green">Green</flux:select.option>
                                            <flux:select.option value="orange">Orange</flux:select.option>
                                            <flux:select.option value="red">Red</flux:select.option>
                                            <flux:select.option value="purple">Purple</flux:select.option>
                                            <flux:select.option value="dark">Dark</flux:select.option>
                                        </flux:select>
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:label for="config_image_url">Image URL</flux:label>
                                        <flux:input id="config_image_url" wire:model.live="config_image_url" placeholder="https://..." />
                                        <flux:error name="config_image_url" />
                                    </flux:field>

                                    <flux:field class="sm:col-span-2">
                                        <flux:label for="config_button_effect">Button Effect</flux:label>
                                        <flux:select id="config_button_effect" wire:model.live="config_button_effect">
                                            <flux:select.option value="none">None (solid colour)</flux:select.option>
                                            <flux:select.option value="gradient_teal_green">Gradient — Teal &amp; Green</flux:select.option>
                                            <flux:select.option value="gradient_blue_purple">Gradient — Blue &amp; Purple</flux:select.option>
                                            <flux:select.option value="gradient_orange_red">Gradient — Orange &amp; Red</flux:select.option>
                                            <flux:select.option value="gradient_rose_pink">Gradient — Rose &amp; Pink</flux:select.option>
                                            <flux:select.option value="gradient_amber_orange">Gradient — Amber &amp; Orange</flux:select.option>
                                            <flux:select.option value="gradient_cyan_blue">Gradient — Cyan &amp; Blue</flux:select.option>
                                            <flux:select.option value="gradient_emerald_teal">Gradient — Emerald &amp; Teal</flux:select.option>
                                            <flux:select.option value="gradient_indigo_purple">Gradient — Indigo &amp; Purple</flux:select.option>
                                            <flux:select.option value="gradient_gold_amber">Gradient — Gold &amp; Amber</flux:select.option>
                                            <flux:select.option value="gradient_pink_purple">Gradient — Pink &amp; Purple</flux:select.option>
                                        </flux:select>
                                    </flux:field>
                                </div>
                            </div>
                        @endif

                        @if ($isButtonLike)
                            <div class="grid gap-4 sm:grid-cols-2">
                                <flux:field class="sm:col-span-2">
                                    <flux:label>Button Effect</flux:label>
                                    <flux:select wire:model.live="config_button_effect">
                                        <flux:select.option value="none">None (solid colour)</flux:select.option>
                                        <flux:select.option value="gradient_teal_green">Gradient — Teal &amp; Green</flux:select.option>
                                        <flux:select.option value="gradient_blue_purple">Gradient — Blue &amp; Purple</flux:select.option>
                                        <flux:select.option value="gradient_orange_red">Gradient — Orange &amp; Red</flux:select.option>
                                        <flux:select.option value="gradient_rose_pink">Gradient — Rose &amp; Pink</flux:select.option>
                                        <flux:select.option value="gradient_amber_orange">Gradient — Amber &amp; Orange</flux:select.option>
                                        <flux:select.option value="gradient_cyan_blue">Gradient — Cyan &amp; Blue</flux:select.option>
                                        <flux:select.option value="gradient_emerald_teal">Gradient — Emerald &amp; Teal</flux:select.option>
                                        <flux:select.option value="gradient_indigo_purple">Gradient — Indigo &amp; Purple</flux:select.option>
                                        <flux:select.option value="gradient_gold_amber">Gradient — Gold &amp; Amber</flux:select.option>
                                        <flux:select.option value="gradient_pink_purple">Gradient — Pink &amp; Purple</flux:select.option>
                                    </flux:select>
                                </flux:field>

                                <flux:field>
                                    <flux:label>Button Colour</flux:label>
                                    <flux:select wire:model.live="config_button_color" :disabled="$config_button_effect !== 'none'">
                                        <flux:select.option value="bg-blue-600 hover:bg-blue-700">Blue</flux:select.option>
                                        <flux:select.option value="bg-teal-600 hover:bg-teal-700">Teal</flux:select.option>
                                        <flux:select.option value="bg-green-600 hover:bg-green-700">Green</flux:select.option>
                                        <flux:select.option value="bg-orange-600 hover:bg-orange-700">Orange</flux:select.option>
                                        <flux:select.option value="bg-red-600 hover:bg-red-700">Red</flux:select.option>
                                        <flux:select.option value="bg-purple-600 hover:bg-purple-700">Purple</flux:select.option>
                                        <flux:select.option value="bg-gray-900 hover:bg-gray-800">Dark</flux:select.option>
                                    </flux:select>
                                </flux:field>

                                <flux:field>
                                    <flux:label>Button Size</flux:label>
                                    <flux:select wire:model.live="config_button_size">
                                        <flux:select.option value="text-sm px-4 py-2">Small</flux:select.option>
                                        <flux:select.option value="text-base px-6 py-3">Medium</flux:select.option>
                                        <flux:select.option value="text-lg px-8 py-4">Large</flux:select.option>
                                    </flux:select>
                                </flux:field>

                                <flux:field>
                                    <flux:label>Corner Radius (px)</flux:label>
                                    <flux:input type="number" min="0" max="100" wire:model.live="config_corner_radius" />
                                </flux:field>

                                <flux:field>
                                    <flux:label>Icon</flux:label>
                                    <flux:select wire:model.live="config_button_icon">
                                        <flux:select.option value="none">No icon</flux:select.option>
                                        <flux:select.option value="heart">Heart</flux:select.option>
                                        <flux:select.option value="hand">Hand</flux:select.option>
                                        <flux:select.option value="star">Star</flux:select.option>
                                        <flux:select.option value="gift">Gift</flux:select.option>
                                        <flux:select.option value="plus">Plus</flux:select.option>
                                    </flux:select>
                                </flux:field>

                                @if ($element->type->value === 'link')
                                    <flux:field>
                                        <flux:label>Alignment</flux:label>
                                        <flux:select wire:model.live="config_alignment">
                                            <flux:select.option value="center">Center</flux:select.option>
                                            <flux:select.option value="left">Left</flux:select.option>
                                            <flux:select.option value="right">Right</flux:select.option>
                                        </flux:select>
                                    </flux:field>
                                @endif

                                @if ($element->type->value === 'sticky_button')
                                    <flux:field>
                                        <flux:label>Position</flux:label>
                                        <flux:select wire:model.live="config_position">
                                            <flux:select.option value="right-center">Middle Right</flux:select.option>
                                            <flux:select.option value="left-center">Middle Left</flux:select.option>
                                        </flux:select>
                                    </flux:field>
                                @endif
                            </div>
                        @endif
                    </div>
                </x-ui.card>

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
                            'alignment' => $config_alignment,
                            'position' => $config_position,
                            'action' => $config_action,
                            'trigger' => $config_trigger,
                            'delay' => $config_delay,
                            'frequency' => $config_frequency,
                            'visibility' => $config_visibility,
                            'layout' => $config_layout,
                            'image_url' => $config_image_url,
                            'color' => $config_color,
                        ], fn ($value) => $value !== null && $value !== ''))"
                    />
                </div>
            </div>
        </div>
    </form>
</div>
