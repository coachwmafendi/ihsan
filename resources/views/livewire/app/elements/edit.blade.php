{{-- resources/views/livewire/app/elements/edit.blade.php --}}
@php
    $isButtonLike = in_array($element->type->value, ['button', 'floating_button', 'sticky_button', 'link'], true);
    $isQrCode = $element->type->value === 'qr_code';
    $embedCode = '<script src="' . url('/e/widget.js') . '" data-token="' . $element->token . '" data-type="' . $element->type->value . '" async></script>';
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
                Type: {{ $element->type->label() }}
            </span>
        </p>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left column: Form fields --}}
            <div class="space-y-6 lg:col-span-2">
                {{-- Embed Code --}}
                <div x-data="{ code: @js($embedCode), copied: false, copy() { navigator.clipboard.writeText(this.code).then(() => { this.copied = true; setTimeout(() => this.copied = false, 2000) }) } }">
                    <x-ui.card title="Embed Code" description="Copy this code to embed on your website">
                        <x-slot:actions>
                            <button
                                type="button"
                                @click="copy()"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-3 py-1.5 text-sm font-medium text-white transition hover:bg-slate-700"
                            >
                                <x-heroicon-o-clipboard-document class="size-4" x-show="!copied" />
                                <x-heroicon-o-check class="size-4" x-show="copied" x-cloak />
                                <span x-show="!copied">Copy</span>
                                <span x-show="copied" x-cloak>Copied!</span>
                            </button>
                        </x-slot:actions>

                        <div class="space-y-3">
                            <textarea
                                x-ref="codeBox"
                                readonly
                                rows="3"
                                @click="$refs.codeBox.select()"
                                class="w-full resize-none rounded-lg bg-slate-900 p-4 font-mono text-xs leading-relaxed text-slate-300 focus:outline-none"
                                style="white-space: pre-wrap; overflow-wrap: break-word"
                            >{{ $embedCode }}</textarea>

                            <div class="flex flex-col gap-1 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between">
                                <span>Paste this inside your page’s <code class="rounded bg-slate-100 px-1 py-0.5 font-mono text-slate-700">&lt;body&gt;</code> tag.</span>
                                <span class="font-mono">Token: {{ $element->token }}</span>
                            </div>
                        </div>
                    </x-ui.card>
                </div>

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
                            <flux:label>{{ $isQrCode ? 'Label' : 'Button Text' }}</flux:label>
                            <flux:input wire:model.live="config_button_text" placeholder="{{ $isQrCode ? 'e.g. Scan to donate' : 'e.g. Donate Now' }}" autocomplete="off" />
                        </flux:field>

                        @if ($isQrCode)
                            <div class="grid gap-4 sm:grid-cols-2">
                                <flux:field>
                                    <flux:label>QR Size</flux:label>
                                    <flux:select wire:model.live="config_size">
                                        <flux:select.option value="small">Small (150px)</flux:select.option>
                                        <flux:select.option value="medium">Medium (200px)</flux:select.option>
                                        <flux:select.option value="large">Large (250px)</flux:select.option>
                                        <flux:select.option value="extra large">Extra Large (300px)</flux:select.option>
                                    </flux:select>
                                </flux:field>

                                <flux:field>
                                    <flux:label>Alignment</flux:label>
                                    <flux:select wire:model.live="config_alignment">
                                        <flux:select.option value="left">Left</flux:select.option>
                                        <flux:select.option value="center">Center</flux:select.option>
                                        <flux:select.option value="right">Right</flux:select.option>
                                    </flux:select>
                                </flux:field>
                            </div>
                        @endif

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
                                        <flux:select.option value="small">Small</flux:select.option>
                                        <flux:select.option value="medium">Medium</flux:select.option>
                                        <flux:select.option value="large">Large</flux:select.option>
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
                    <x-ui.button wire:click="confirmArchive" variant="danger">Archive Element</x-ui.button>
                    <div class="flex items-center gap-3">
                        <x-ui.button href="{{ route('app.elements.index') }}" variant="ghost">Cancel</x-ui.button>
                        <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
                    </div>
                </div>
            </div>

            {{-- Right column: Preview --}}
            <div class="lg:col-span-1">
                <div class="sticky top-6 space-y-6">
                    @if ($isButtonLike)
                        @php
                            $previewSizeMap = [
                                'small' => ['padding' => '8px 16px', 'font' => '14px'],
                                'medium' => ['padding' => '12px 24px', 'font' => '16px'],
                                'large' => ['padding' => '16px 32px', 'font' => '18px'],
                            ];
                            $previewColorMap = [
                                'bg-blue-600 hover:bg-blue-700' => '#2563eb',
                                'bg-teal-600 hover:bg-teal-700' => '#0d9488',
                                'bg-green-600 hover:bg-green-700' => '#16a34a',
                                'bg-orange-600 hover:bg-orange-700' => '#ea580c',
                                'bg-red-600 hover:bg-red-700' => '#dc2626',
                                'bg-purple-600 hover:bg-purple-700' => '#9333ea',
                                'bg-gray-900 hover:bg-gray-800' => '#1f2937',
                            ];
                            $previewIcons = [
                                'heart' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>',
                                'hand' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M23 12.22V15c0 4.97-4.03 9-9 9H9.17c-1.59 0-3.11-.63-4.24-1.76L0 17.5l1.5-1.5c.47-.47 1.08-.73 1.77-.73.46 0 .9.12 1.28.35L7 17.34V4.5C7 3.67 7.67 3 8.5 3S10 3.67 10 4.5v4h1V3.5C11 2.67 11.67 2 12.5 2S14 2.67 14 3.5V8.5h1V4c0-.83.67-1.5 1.5-1.5S18 3.17 18 4v5.5h1V6c0-.83.67-1.5 1.5-1.5S22 5.17 22 6v6.22z"/></svg>',
                                'star' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>',
                                'gift' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M20 6h-2.18c.11-.31.18-.65.18-1 0-1.66-1.34-3-3-3-1.05 0-1.96.54-2.5 1.35l-.5.67-.5-.68C10.96 2.54 10.05 2 9 2 7.34 2 6 3.34 6 5c0 .35.07.69.18 1H4c-1.11 0-1.99.89-1.99 2L2 19c0 1.11.89 2 2 2h16c1.11 0 2-.89 2-2V8c0-1.11-.89-2-2-2zm-5-2c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zM9 4c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm11 15H4v-2h16v2zm0-5H4V8h5.08L7 10.83 8.62 12 12 7.01l3.38 4.99L17 10.83 14.92 8H20v6z"/></svg>',
                                'plus' => '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor"><path d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>',
                            ];
                            $effectGradients = [
                                'gradient_teal_green' => 'linear-gradient(120deg,#0d9488,#14b8a6,#22c55e,#0d9488)',
                                'gradient_blue_purple' => 'linear-gradient(120deg,#2563eb,#7c3aed,#a855f7,#2563eb)',
                                'gradient_orange_red' => 'linear-gradient(120deg,#ea580c,#dc2626,#f97316,#ea580c)',
                                'gradient_rose_pink' => 'linear-gradient(120deg,#f43f5e,#ec4899,#fb7185,#f43f5e)',
                                'gradient_amber_orange' => 'linear-gradient(120deg,#f59e0b,#ea580c,#fbbf24,#f59e0b)',
                                'gradient_cyan_blue' => 'linear-gradient(120deg,#06b6d4,#2563eb,#38bdf8,#06b6d4)',
                                'gradient_emerald_teal' => 'linear-gradient(120deg,#10b981,#0d9488,#34d399,#10b981)',
                                'gradient_indigo_purple' => 'linear-gradient(120deg,#6366f1,#7c3aed,#818cf8,#6366f1)',
                                'gradient_gold_amber' => 'linear-gradient(120deg,#eab308,#f59e0b,#fcd34d,#eab308)',
                                'gradient_pink_purple' => 'linear-gradient(120deg,#ec4899,#a855f7,#f472b6,#ec4899)',
                            ];
                            $effectShadows = [
                                'gradient_teal_green' => '0 8px 20px rgba(13,148,136,.4)',
                                'gradient_blue_purple' => '0 8px 20px rgba(37,99,235,.4)',
                                'gradient_orange_red' => '0 8px 20px rgba(234,88,12,.4)',
                                'gradient_rose_pink' => '0 8px 20px rgba(244,63,94,.4)',
                                'gradient_amber_orange' => '0 8px 20px rgba(245,158,11,.4)',
                                'gradient_cyan_blue' => '0 8px 20px rgba(6,182,212,.4)',
                                'gradient_emerald_teal' => '0 8px 20px rgba(16,185,129,.4)',
                                'gradient_indigo_purple' => '0 8px 20px rgba(99,102,241,.4)',
                                'gradient_gold_amber' => '0 8px 20px rgba(234,179,8,.4)',
                                'gradient_pink_purple' => '0 8px 20px rgba(236,72,153,.4)',
                            ];
                            $ps = $previewSizeMap[$config_button_size ?? 'medium'] ?? $previewSizeMap['medium'];
                            $pc = $previewColorMap[$config_button_color ?? 'bg-blue-600 hover:bg-blue-700'] ?? '#2563eb';
                            $pr = (int) ($config_corner_radius ?? 8);
                            $pIcon = $previewIcons[$config_button_icon ?? 'heart'] ?? $previewIcons['heart'];
                            $pText = $config_button_text ?? 'Donate Now';
                            $pEffect = $config_button_effect ?? 'none';
                            $hasEffect = $pEffect !== 'none' && isset($effectGradients[$pEffect]);
                            $effectId = 'preview-' . md5($pEffect);
                            $sbPosition = $config_position ?? 'right-center';
                            $sbIsLeft = str_starts_with($sbPosition, 'left');
                        @endphp
                        <div class="rounded-xl border border-zinc-200 bg-white">
                            <div class="border-b border-zinc-100 px-4 py-2">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Preview</h3>
                                    <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600">{{ $element->type->label() }}</span>
                                </div>
                            </div>

                            @if ($element->type->value === 'button' || $element->type->value === 'floating_button')
                                @if ($hasEffect)
                                    <style>
                                        @keyframes preview-gradient-{{ $effectId }} {
                                            0%   { background-position: 0% 50%; }
                                            50%  { background-position: 100% 50%; }
                                            100% { background-position: 0% 50%; }
                                        }
                                        .preview-effect-{{ $effectId }} {
                                            background: {{ $effectGradients[$pEffect] }};
                                            background-size: 300% 300%;
                                            animation: preview-gradient-{{ $effectId }} 4s ease infinite;
                                            box-shadow: {{ $effectShadows[$pEffect] }};
                                            transition: transform .2s ease, box-shadow .2s ease;
                                        }
                                        .preview-effect-{{ $effectId }}:hover {
                                            transform: translateY(-2px);
                                            box-shadow: {{ str_replace('.4)', '.55)', $effectShadows[$pEffect]) }};
                                        }
                                    </style>
                                @endif
                                <div class="flex min-h-[120px] items-center justify-center bg-zinc-50 px-4 py-6">
                                    <button
                                        type="button"
                                        class="inline-flex items-center justify-center gap-2 font-semibold text-white focus:outline-none @if ($hasEffect) preview-effect-{{ $effectId }} @endif"
                                        style="padding: {{ $ps['padding'] }}; font-size: {{ $ps['font'] }}; border-radius: {{ $pr }}px; @if (! $hasEffect) background-color: {{ $pc }}; @endif"
                                    >
                                        {!! $pIcon !!}
                                        <span>{{ $pText }}</span>
                                    </button>
                                </div>

                            @elseif ($element->type->value === 'sticky_button')
                                @php
                                    $stickySizeMap = [
                                        'small' => ['width' => '44px', 'font' => '13px', 'innerPadding' => '0 10px'],
                                        'medium' => ['width' => '52px', 'font' => '15px', 'innerPadding' => '0 14px'],
                                        'large' => ['width' => '60px', 'font' => '17px', 'innerPadding' => '0 18px'],
                                    ];
                                    $ss = $stickySizeMap[$config_button_size ?? 'medium'] ?? $stickySizeMap['medium'];
                                    $sbMinHeight = max(120, (mb_strlen($pText) * 10) + 60);
                                    $prInt = (int) ($config_corner_radius ?? 8);
                                    $sbBorderRadius = $sbIsLeft ? "0 {$prInt}px {$prInt}px 0" : "{$prInt}px 0 0 {$prInt}px";
                                    $sbRotation = $sbIsLeft ? 'rotate(90deg)' : 'rotate(-90deg)';
                                @endphp
                                <div class="relative flex min-h-[300px] w-full items-center justify-center rounded-lg border-2 border-dashed border-zinc-200 bg-white/60 p-6">
                                    <div
                                        class="relative inline-flex items-center justify-center text-white shadow-2xl transition-transform hover:scale-105"
                                        style="background-color: {{ $pc }}; padding: 14px 0; border-radius: {{ $sbBorderRadius }}; width: {{ $ss['width'] }}; min-height: {{ $sbMinHeight }}px;"
                                    >
                                        <div class="absolute left-1/2 top-1/2 inline-flex items-center justify-center gap-1.5" style="transform: translate(-50%, -50%) {{ $sbRotation }}; white-space: nowrap; font-size: {{ $ss['font'] }}; font-weight: 700; letter-spacing: .08em;">
                                            <svg viewBox="0 0 24 24" fill="currentColor" width="18" height="18">{!! $pIcon !!}</svg>
                                            <span>{{ $pText }}</span>
                                        </div>
                                    </div>
                                    <span class="absolute bottom-2 text-xs text-zinc-400">Preview shown centered — on site it sticks to the {{ $sbIsLeft ? 'left' : 'right' }} edge</span>
                                </div>

                            @elseif ($element->type->value === 'link')
                                <div class="flex min-h-[120px] items-center justify-center bg-zinc-50 px-4 py-6">
                                    <div class="text-center">
                                        <span
                                            class="inline-flex items-center justify-center gap-2 font-semibold text-white shadow-sm"
                                            style="padding: {{ $ps['padding'] }}; font-size: {{ $ps['font'] }}; border-radius: {{ $pr }}px; background-color: {{ $pc }};"
                                        >
                                            {!! $pIcon !!}
                                            <span>{{ $pText }}</span>
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        {{-- Blade preview for QR, Popup, Form types --}}
                        <x-element-preview
                            :type="$element->type"
                            :config="array_merge($element->config ?? [], array_filter([
                                'title' => $config_title,
                                'message' => $config_message,
                                'button_text' => $config_button_text,
                                'submit_text' => $config_button_text,
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
                                'size' => $config_size,
                                'qr_url' => $isQrCode ? route('donations.show', ['element' => $element->token]) : null,
                            ], fn ($value) => $value !== null && $value !== ''))"
                        />
                    @endif
                </div>
            </div>
        </div>
    </form>

    {{-- Archive Confirmation Modal --}}
    @if ($showArchiveModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="$set('showArchiveModal', false)"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Archive this element?</h2>
                    <button wire:click="$set('showArchiveModal', false)" class="text-slate-400 hover:text-slate-600">
                        <x-heroicon-o-x-mark class="size-5" />
                    </button>
                </div>
                <div class="px-6 py-5">
                    <p class="text-sm text-slate-600">This element will stop showing on your website. It will not be deleted and can be recovered by support if needed.</p>
                </div>
                <div class="flex items-center justify-end gap-3 border-t border-slate-200 px-6 py-4">
                    <x-ui.button wire:click="$set('showArchiveModal', false)" variant="secondary">Cancel</x-ui.button>
                    <x-ui.button wire:click="archive" variant="danger">
                        <span wire:loading.remove wire:target="archive">Archive</span>
                        <span wire:loading wire:target="archive">Archiving...</span>
                    </x-ui.button>
                </div>
            </div>
        </div>
    @endif
</div>
