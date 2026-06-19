{{-- resources/views/livewire/app/campaigns/edit.blade.php --}}
<div x-data="{ tab: @entangle('activeTab') }" class="space-y-6">
    {{-- Page Header --}}
    <div class="mb-4">
        <a href="{{ route('app.campaigns.index') }}" wire:navigate class="inline-flex items-center text-sm text-slate-500 hover:text-slate-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back
        </a>
    </div>
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $campaign->title }}</h1>
                <x-ui.badge status="{{ $campaign->status->value }}" size="sm">
                    {{ ucfirst($campaign->status->value) }}
                </x-ui.badge>
            </div>
            <div class="mt-1.5 flex flex-wrap items-center gap-x-2 text-sm text-slate-500">
                <span class="inline-flex items-center gap-1.5">
                    ID {{ $campaign->public_id }}
                    <x-ui.copy-button value="{{ $campaign->public_id }}" title="Copy ID" class="p-0.5" />
                </span>
                <span>·</span>
                <span>Created {{ $campaign->created_at->format('M d, Y') }}</span>
                <span>·</span>
                <span>{{ $campaign->end_date ? 'Ends '.$campaign->end_date->format('M d, Y') : 'No end date' }}</span>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button type="button"
                @click="tab = 'overview'"
                :class="tab === 'overview' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Overview
            </button>
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
                @click="tab = 'campaign-page'"
                :class="tab === 'campaign-page' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Campaign Page
            </button>
            <button type="button"
                @click="tab = 'actions'"
                :class="tab === 'actions' ? 'border-teal-500 text-teal-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Actions
            </button>
        </nav>
    </div>

    {{-- Overview Tab (read-only campaign summary) --}}
    <div x-show="tab === 'overview'" x-cloak class="space-y-6">
        {{-- Stats Cards --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-ui.stat-card
                label="Amount Raised"
                value="{{ $this->getCurrencySymbol() }} {{ number_format((float) $campaign->collected_amount, 2) }}"
                subtext="{{ $campaign->has_target && $campaign->target_amount ? 'Goal: '.$this->getCurrencySymbol().' '.number_format((float) $campaign->target_amount, 2) : 'No target set' }}"
            />
            <x-ui.stat-card
                label="Donations"
                value="{{ number_format($campaign->donations()->count()) }}"
            />
            <x-ui.stat-card
                label="Active Recurring"
                value="{{ number_format($campaign->subscriptions()->whereIn('status', ['active', 'trialing'])->count()) }}"
            />
            <x-ui.stat-card
                label="Last Donation"
                value="{{ $campaign->donations()->latest()->first()?->created_at?->diffForHumans() ?? '—' }}"
                subtext="{{ $campaign->donations()->latest()->first()?->donor?->name ?? '' }}"
            />
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {{-- Left Column --}}
            <div class="lg:col-span-2 space-y-6">
                <x-ui.card title="Configuration">
                    <x-slot:actions>
                        <button type="button" wire:click="$set('activeTab', 'settings')" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                            Edit Settings
                        </button>
                        <button type="button" wire:click="$set('activeTab', 'checkout')" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                            Edit Checkout
                        </button>
                    </x-slot:actions>

                    <dl class="space-y-4">
                        {{-- Goal & Duration --}}
                        <div
                            class="group cursor-pointer rounded-lg transition-colors hover:bg-slate-50"
                            @click="tab = 'settings'; $nextTick(() => document.getElementById('campaign-settings-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' }))"
                            role="button"
                            tabindex="0"
                            title="Edit in Campaign Settings"
                        >
                            <div class="flex items-center justify-between">
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500 group-hover:text-slate-600">Goal &amp; Duration</dt>
                                <x-heroicon-m-pencil class="size-3.5 text-slate-400 opacity-0 transition-opacity group-hover:opacity-100" />
                            </div>
                            <dd class="mt-0.5 text-sm text-slate-900">
                                @if ($campaign->has_target && $campaign->target_amount)
                                    Target {{ $this->getCurrencySymbolFor($this->default_currency) }} {{ number_format((float) $campaign->target_amount, 2) }}
                                @else
                                    No target
                                @endif
                                ·
                                @if ($campaign->has_end_date && $campaign->end_date)
                                    Ends {{ $campaign->end_date->format('M d, Y') }}
                                @else
                                    No end date
                                @endif
                            </dd>
                        </div>

                        {{-- Description --}}
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Description</dt>
                            <dd class="mt-0.5 text-sm text-slate-900 leading-relaxed">
                                {{ $campaign->description ? \Illuminate\Support\Str::limit($campaign->description, 150) : 'Not set' }}
                            </dd>
                        </div>

                        {{-- Donation Options --}}
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Donation Options</dt>
                            <dd class="mt-1.5 flex flex-wrap gap-2">
                                <x-ui.badge status="{{ $campaign->allow_recurring ? 'success' : 'default' }}" size="sm">
                                    {{ $campaign->allow_recurring ? 'Recurring on' : 'Recurring off' }}
                                </x-ui.badge>
                                <x-ui.badge status="{{ $campaign->allow_custom_amount ? 'success' : 'default' }}" size="sm">
                                    {{ $campaign->allow_custom_amount ? 'Custom amount on' : 'Custom amount off' }}
                                </x-ui.badge>
                                @if ($campaign->minimum_amount)
                                    <x-ui.badge status="default" size="sm">Min {{ $this->getCurrencySymbolFor($this->default_currency) }} {{ number_format((float) $campaign->minimum_amount, 2) }}</x-ui.badge>
                                @endif
                            </dd>
                        </div>

                        {{-- Checkout Defaults --}}
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Checkout Defaults</dt>
                            <dd class="mt-0.5 text-sm text-slate-900 leading-relaxed">
                                Default frequency: <strong>{{ ucfirst(str_replace('_', ' ', $this->default_frequency)) }}</strong><br>
                                Default amount: <strong>{{ $this->getCurrencySymbolFor($this->default_currency) }} {{ number_format((float) ($this->default_amount ?? 50), 2) }}</strong><br>
                                Default currency: <strong>{{ $this->default_currency }}</strong><br>
                                Currency auto-detect: <strong>{{ $this->currency_autodetect ? 'On' : 'Off' }}</strong>
                            </dd>
                        </div>

                        {{-- Checkout Fields --}}
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Checkout Fields</dt>
                            <dd class="mt-1.5 flex flex-wrap gap-2">
                                <x-ui.badge status="{{ $this->allow_cover_fee ? 'success' : 'default' }}" size="sm">
                                    Cover fee {{ $this->allow_cover_fee ? 'on' : 'off' }}
                                </x-ui.badge>
                                <x-ui.badge status="{{ $this->show_comment ? 'success' : 'default' }}" size="sm">
                                    Comment {{ $this->show_comment ? 'on' : 'off' }}
                                </x-ui.badge>
                                <x-ui.badge status="{{ $this->show_phone ? 'success' : 'default' }}" size="sm">
                                    Phone {{ $this->show_phone ? 'on' : 'off' }}
                                </x-ui.badge>
                            </dd>
                        </div>

                        {{-- Post Donation --}}
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Post Donation</dt>
                            <dd class="mt-0.5 text-sm text-slate-900 leading-relaxed">
                                Thank-you message: <strong>{{ $campaign->thank_you_message ? 'Set' : 'Not set' }}</strong><br>
                                Redirect URL: <strong>{{ $campaign->redirect_url ?: 'None' }}</strong>
                            </dd>
                        </div>

                        {{-- Suggested Amounts --}}
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Suggested Amounts ({{ $this->default_currency }})</dt>
                            <dd class="mt-0.5 text-sm text-slate-900 leading-relaxed">
                                @php $defaultAmounts = $this->getDefaultCurrencySuggestedAmounts(); @endphp
                                One-time:
                                <strong>
                                    {{ collect($defaultAmounts['one_time'] ?? [])->pluck('value')->filter(fn ($v) => $v > 0)->implode(', ') ?: 'None' }}
                                </strong>
                                <br>
                                Monthly:
                                <strong>
                                    {{ collect($defaultAmounts['monthly'] ?? [])->pluck('value')->filter(fn ($v) => $v > 0)->implode(', ') ?: 'None' }}
                                </strong>
                            </dd>
                        </div>
                    </dl>
                </x-ui.card>

                <x-ui.card title="Recent Donations" description="Last 10 donations to this campaign">
                    @php $recent = $campaign->donations()->with('donor')->latest()->limit(10)->get(); @endphp
                    @if ($recent->isNotEmpty())
                        <div class="divide-y divide-slate-100">
                            @foreach ($recent as $donation)
                                <div class="flex items-center justify-between py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-slate-50">
                                            <x-heroicon-o-user class="size-4 text-slate-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ $donation->donor?->name ?? 'Anonymous' }}</p>
                                            <p class="text-xs text-slate-500">{{ $donation->created_at->format('M d, Y H:i') }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-semibold text-slate-900">{{ $this->getCurrencySymbol() }} {{ number_format((float) $donation->gross_amount, 2) }}</p>
                                        <x-ui.badge status="{{ $donation->status->value }}" size="sm">{{ ucfirst($donation->status->value) }}</x-ui.badge>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-banknotes"
                            title="No donations yet"
                            description="Donations to this campaign will appear here."
                        />
                    @endif
                </x-ui.card>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">
                <x-ui.card title="Linked Elements" description="Embed elements using this campaign">
                    @php $elements = $campaign->elements()->select(['id','public_id','token','name','type','created_at'])->get(); @endphp
                    @if ($elements->isNotEmpty())
                        <div class="divide-y divide-slate-100">
                            @foreach ($elements as $element)
                                @php
                                    $embedCode = '<script src="' . url('/e/widget.js') . '" data-token="' . $element->token . '" data-type="' . $element->type->value . '" async></script>';
                                    $typeLabel = ucfirst(str_replace('_', ' ', $element->type->value));
                                @endphp
                                <a
                                    href="{{ route('app.elements.edit', $element) }}"
                                    wire:navigate
                                    class="group flex items-center justify-between py-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50 group-hover:bg-teal-50 transition-colors">
                                            <x-heroicon-o-code-bracket class="size-4 text-slate-400 group-hover:text-teal-500 transition-colors" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900 group-hover:text-teal-700 transition-colors">{{ $element->name }}</p>
                                            <p class="text-xs text-slate-500">{{ $typeLabel }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2" x-data="{ copied: false }">
                                        <code class="rounded bg-slate-50 px-2 py-0.5 text-xs text-slate-600 font-mono group-hover:bg-slate-100 transition-colors">{{ $element->public_id }}</code>
                                        <button
                                            type="button"
                                            title="Copy embed code"
                                            @click.stop.prevent="navigator.clipboard.writeText(@js($embedCode)); copied = true; setTimeout(() => copied = false, 2000)"
                                            class="shrink-0 rounded p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition-colors"
                                            aria-label="Copy embed code"
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
                                </a>
                            @endforeach
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-code-bracket"
                            title="No elements"
                            description="Create an embed element linked to this campaign."
                        />
                    @endif
                </x-ui.card>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Settings Tab --}}
        <div x-show="tab === 'settings'" x-cloak id="campaign-settings-section" class="space-y-6">
            <x-ui.card title="Basic Information">
                <div class="space-y-5">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                        <div>
                            <label for="title" class="block text-sm font-medium text-slate-700">Campaign Title <span class="text-red-500">*</span></label>
                            <input
                                type="text"
                                id="title"
                                wire:model="title"
                                class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="e.g. Ramadan Fundraiser 2026"
                            />
                            @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="status" class="block text-sm font-medium text-slate-700">Status <span class="text-red-500">*</span></label>
                            <x-ui.select id="status" wire:model="status" class="mt-1.5 block w-full" style="max-width: 11rem;">
                                <flux:select.option value="draft">Draft</flux:select.option>
                                <flux:select.option value="active">Active</flux:select.option>
                                <flux:select.option value="paused">Paused</flux:select.option>
                                <flux:select.option value="ended">Ended</flux:select.option>
                                <flux:select.option value="archived">Archived</flux:select.option>
                            </x-ui.select>
                            @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea
                            id="description"
                            wire:model="description"
                            rows="3"
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            placeholder="Describe the purpose of this campaign..."
                        ></textarea>
                        @error('description') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="image" class="block text-sm font-medium text-slate-700">Campaign Image</label>
                        <div class="mt-1.5 flex items-center gap-4">
                            @if ($image)
                                <img src="{{ $image->temporaryUrl() }}" alt="New campaign image preview" class="h-20 w-20 rounded-lg object-cover border border-slate-200" />
                            @elseif ($existing_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($existing_image))
                                <img src="{{ Storage::disk('public')->url($existing_image) }}" alt="Current campaign image" class="h-20 w-20 rounded-lg object-cover border border-slate-200" />
                            @else
                                <div class="flex h-20 w-20 items-center justify-center rounded-lg bg-slate-100 text-slate-400">
                                    <x-heroicon-o-photo class="size-8" />
                                </div>
                            @endif
                            <div class="flex flex-col gap-1.5">
                                <input
                                    type="file"
                                    id="image"
                                    wire:model.live="image"
                                    accept="image/*"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100"
                                />
                                @if ($existing_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($existing_image))
                                    <button type="button" wire:click="removeImage" class="self-start text-xs text-slate-500 hover:text-red-700 hover:underline">Remove image</button>
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

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Campaign Goal --}}
                <x-ui.card>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <h3 class="text-sm font-medium text-slate-900">Campaign Goal</h3>
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
                            <div class="space-y-1.5">
                                <label for="target_amount" class="block text-sm font-medium text-slate-700">Target Amount</label>
                                <div class="relative" style="max-width: 11rem;">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-slate-500">RM</span>
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="7"
                                        pattern="[0-9]*"
                                        id="target_amount"
                                        wire:model="target_amount"
                                        x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').substring(0, 7)"
                                        class="block w-full rounded-lg border border-slate-300 bg-white py-2 pl-10 pr-3 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        placeholder="10000"
                                    />
                                </div>
                                @error('target_amount') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No fundraising target set.</p>
                        @endif
                    </div>
                </x-ui.card>

                {{-- Campaign Duration --}}
                <x-ui.card>
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <h3 class="text-sm font-medium text-slate-900">Campaign Duration</h3>
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
                            <div class="space-y-1.5">
                                <label for="end_date" class="block text-sm font-medium text-slate-700">End Date</label>
                                <input
                                    type="date"
                                    id="end_date"
                                    wire:model="end_date"
                                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    style="max-width: 13rem;"
                                />
                                @error('end_date') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @else
                            <p class="text-sm text-slate-500">This campaign runs indefinitely.</p>
                        @endif
                    </div>
                </x-ui.card>
            </div>

            <div class="flex items-center justify-end gap-3">
                <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
            </div>
        </div>

        {{-- Checkout Modal Tab --}}
        <div x-show="tab === 'checkout'" x-cloak class="space-y-6">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
                {{-- Left Column: Vertical Tabs --}}
                <div class="lg:col-span-1">
                    <x-ui.card>
                        <nav class="flex flex-col space-y-1" aria-label="Checkout settings">
                            <button type="button"
                                wire:click="$set('checkoutPanel', 'currency')"
                                class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $checkoutPanel === 'currency' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                            >
                                <x-heroicon-o-currency-dollar class="size-5" />
                                Currency
                            </button>
                            <button type="button"
                                wire:click="$set('checkoutPanel', 'frequency')"
                                class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $checkoutPanel === 'frequency' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                            >
                                <x-heroicon-o-arrow-path class="size-5" />
                                Frequency
                            </button>
                            <button type="button"
                                wire:click="$set('checkoutPanel', 'suggested')"
                                class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $checkoutPanel === 'suggested' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                            >
                                <x-heroicon-o-list-bullet class="size-5" />
                                Suggested Amounts
                            </button>
                            <button type="button"
                                wire:click="$set('checkoutPanel', 'minimum')"
                                class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $checkoutPanel === 'minimum' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                            >
                                <x-heroicon-o-arrow-down-circle class="size-5" />
                                Minimum Amounts
                            </button>
                            <button type="button"
                                wire:click="$set('checkoutPanel', 'fee')"
                                class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $checkoutPanel === 'fee' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                            >
                                <x-heroicon-o-credit-card class="size-5" />
                                Processing Fee
                            </button>
                            <button type="button"
                                wire:click="$set('checkoutPanel', 'comment')"
                                class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $checkoutPanel === 'comment' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                            >
                                <x-heroicon-o-chat-bubble-left-right class="size-5" />
                                Comment
                            </button>
                            <button type="button"
                                wire:click="$set('checkoutPanel', 'phone')"
                                class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $checkoutPanel === 'phone' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                            >
                                <x-heroicon-o-phone class="size-5" />
                                Phone
                            </button>
                        </nav>
                    </x-ui.card>
                </div>

                {{-- Right Column: Tab Content --}}
                <div class="lg:col-span-3">
                    <x-ui.card>
                        @if ($checkoutPanel === 'currency')
                            <div class="space-y-6">
                                <p class="text-sm text-slate-600">
                                    Choose a default currency for this campaign's Checkout, and decide whether to give your supporters the option to choose their own. If collecting donations from a range of locations, you can enable currency autodetect.
                                </p>

                                <div class="max-w-md">
                                    <label for="default_currency" class="block text-sm font-semibold text-slate-900">Default Checkout currency</label>
                                <x-ui.select id="default_currency" wire:model="default_currency" class="mt-2 block w-full">
                                    @foreach ($acceptedCurrencies as $currency)
                                        <flux:select.option value="{{ $currency }}">
                                            {{ $currency }} · {{ match($currency) {
                                                'MYR' => 'Malaysian Ringgit',
                                                'USD' => 'United States Dollar',
                                                'SGD' => 'Singapore Dollar',
                                                'GBP' => 'British Pound',
                                                'EUR' => 'Euro',
                                                'AUD' => 'Australian Dollar',
                                                default => $currency,
                                            } }}
                                        </flux:select.option>
                                    @endforeach
                                </x-ui.select>
                                </div>

                                <label class="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="checkbox"
                                        wire:model.live="currency_autodetect"
                                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                    />
                                    <span class="text-sm text-slate-700">
                                        Automatically detect each supporter's default currency using their geolocation.
                                    </span>
                                </label>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                                    <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                                </div>
                            </div>
                        @endif

                        @if ($checkoutPanel === 'frequency')
                            <div class="space-y-6">
                                <div>
                                    <label for="default_frequency" class="block text-sm font-medium text-slate-700">Default Frequency</label>
                                    <x-ui.select id="default_frequency" wire:model="default_frequency" class="mt-1 block w-full">
                                        <flux:select.option value="one_time">One-time</flux:select.option>
                                        <flux:select.option value="monthly">Monthly</flux:select.option>
                                    </x-ui.select>
                                    <p class="mt-1 text-xs text-slate-500">Frequency shown to donors when the page loads.</p>
                                </div>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                                    <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                                </div>
                            </div>
                        @endif

                        @if ($checkoutPanel === 'suggested')
                            <div class="space-y-5">
                                <div>
                                    <span class="mb-2 block text-sm font-semibold text-slate-900">Suggested donation amount presets</span>
                                    <div class="flex items-center gap-2 rounded-xl bg-slate-100 p-1.5">
                                        <button type="button"
                                            wire:click="$set('suggestedActiveFreq', 'one_time')"
                                            class="relative flex-1 rounded-lg px-4 py-3 text-sm font-semibold transition {{ $suggestedActiveFreq === 'one_time' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                                        >
                                            <div class="flex items-center justify-center gap-2">
                                                <x-heroicon-o-bolt class="size-4" />
                                                One Time
                                            </div>
                                        </button>
                                        <button type="button"
                                            wire:click="$set('suggestedActiveFreq', 'monthly')"
                                            class="relative flex-1 rounded-lg px-4 py-3 text-sm font-semibold transition {{ $suggestedActiveFreq === 'monthly' ? 'bg-white text-teal-700 shadow-sm' : 'text-slate-500 hover:text-slate-700' }}"
                                        >
                                            <div class="flex items-center justify-center gap-2">
                                                <x-heroicon-o-arrow-path class="size-4" />
                                                Monthly
                                            </div>
                                        </button>
                                    </div>
                                </div>

                                @if ($suggestedActiveFreq === 'one_time')
                                    <div class="space-y-3">
                                        <span class="text-base font-medium text-slate-700">One Time Amounts</span>
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach ($suggestedOneTime as $index => $amount)
                                                <div class="inline-flex min-h-14 items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-lg font-semibold text-teal-700">
                                                    <span>{{ $this->getCurrencySymbol() }}</span>
                                                    <input
                                                        type="text"
                                                        inputmode="numeric"
                                                        maxlength="5"
                                                        pattern="[0-9]*"
                                                        wire:model.blur="suggestedOneTime.{{ $index }}.value"
                                                        x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').substring(0, 5)"
                                                        class="w-20 rounded-md border border-slate-200 bg-white px-2 py-1 text-lg font-semibold text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                                    />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if ($suggestedActiveFreq === 'monthly')
                                    <div class="space-y-3">
                                        <span class="text-base font-medium text-slate-700">Monthly Amounts</span>
                                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach ($suggestedMonthly as $index => $amount)
                                                <div class="inline-flex min-h-14 items-center gap-2 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-lg font-semibold text-teal-700">
                                                    <span>{{ $this->getCurrencySymbol() }}</span>
                                                    <input
                                                        type="text"
                                                        inputmode="numeric"
                                                        maxlength="5"
                                                        pattern="[0-9]*"
                                                        wire:model.blur="suggestedMonthly.{{ $index }}.value"
                                                        x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').substring(0, 5)"
                                                        class="w-20 rounded-md border border-slate-200 bg-white px-2 py-1 text-lg font-semibold text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                                    />
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                <div class="border-t border-slate-100"></div>

                                <div class="max-w-xs">
                                    <label for="default_amount" class="block text-sm font-medium text-slate-700">Default Amount ({{ $this->getCurrencySymbol() }})</label>
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="5"
                                        pattern="[0-9]*"
                                        id="default_amount"
                                        wire:model="default_amount"
                                        x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').substring(0, 5)"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        placeholder="50"
                                    />
                                </div>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                                    <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                                </div>
                            </div>
                        @endif

                        @if ($checkoutPanel === 'minimum')
                            <div class="space-y-4">
                                <div class="max-w-xs">
                                    <label for="minimum_amount" class="block text-sm font-medium text-slate-700">Minimum Amount ({{ $this->getCurrencySymbol() }})</label>
                                    <input
                                        type="text"
                                        inputmode="numeric"
                                        maxlength="5"
                                        pattern="[0-9]*"
                                        id="minimum_amount"
                                        wire:model="minimum_amount"
                                        x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').substring(0, 5)"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        placeholder="5"
                                    />
                                    @error('minimum_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                </div>
                                <p class="text-xs text-slate-500">Donors will not be able to donate less than this amount.</p>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                                    <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                                </div>
                            </div>
                        @endif

                        @if ($checkoutPanel === 'fee')
                            <div class="space-y-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <h3 class="text-sm font-medium text-slate-900">Allow Donor to Cover Fee</h3>
                                        <p class="text-xs text-slate-500">Donors can opt-in to cover the processing fee so the NGO receives the full amount.</p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="toggleAllowCoverFee"
                                        class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-teal-600 focus:ring-offset-2 {{ $allow_cover_fee ? 'bg-teal-600' : 'bg-slate-200' }}"
                                        role="switch"
                                        aria-checked="{{ $allow_cover_fee ? 'true' : 'false' }}"
                                    >
                                        <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $allow_cover_fee ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                    </button>
                                </div>

                                <div class="rounded-lg bg-slate-50 p-4">
                                    <div class="flex items-start gap-2 text-sm text-slate-700">
                                        <x-heroicon-o-information-circle class="mt-0.5 size-5 shrink-0 text-slate-400" />
                                        <span>Processing fee is <strong>{{ number_format((float) config('services.stripe.processing_fee_percent', 2.5), 1) }}%</strong> plus a fixed fee per transaction.</span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                                    <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                                </div>
                            </div>
                        @endif

                        @if ($checkoutPanel === 'comment')
                            <div class="space-y-6">
                                <p class="text-sm text-slate-600">
                                    Allow your supporters to add a personal note or special instructions for your organization.
                                </p>

                                <label class="flex cursor-pointer items-center gap-3">
                                    <input
                                        type="checkbox"
                                        wire:model.live="show_comment"
                                        class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                    />
                                    <span class="text-sm font-medium text-slate-700">Enable comment</span>
                                </label>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                                    <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                                </div>
                            </div>
                        @endif

                        @if ($checkoutPanel === 'phone')
                            <div class="space-y-6">
                                <p class="text-sm text-slate-600">
                                    Ask donors for their phone number during checkout. This is optional for donors.
                                </p>

                                <label class="flex cursor-pointer items-center gap-3">
                                    <input
                                        type="checkbox"
                                        wire:model.live="show_phone"
                                        class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                    />
                                    <span class="text-sm font-medium text-slate-700">Enable phone</span>
                                </label>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                                    <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                                </div>
                            </div>
                        @endif
                    </x-ui.card>
                </div>
            </div>
        </div>
    </form>

    {{-- Campaign Page Tab --}}
    <div x-show="tab === 'campaign-page'" x-cloak class="space-y-6">
        <x-ui.card title="Campaign Page">
            <div class="space-y-6">
                <div>
                    <h3 class="text-sm font-medium text-slate-900">Thank you screen</h3>
                    <div class="mt-3 space-y-3">
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="radio" wire:model="postDonationMode" value="default" class="mt-0.5 h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-600" />
                            <span class="text-sm text-slate-700">Show supporters the default thank you screen.</span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3">
                            <input type="radio" wire:model="postDonationMode" value="redirect" class="mt-0.5 h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-600" />
                            <span class="text-sm text-slate-700">Redirect supporters to a specific URL.</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">Sharing URL</label>
                    <input type="text" readonly value="{{ route('donations.campaign-show', $campaign) }}" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-600" />
                </div>

                <div>
                    <label for="share_message" class="block text-sm font-medium text-slate-700">Default sharing message</label>
                    <textarea id="share_message" wire:model="shareMessage" rows="3" maxlength="280" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"></textarea>
                </div>
            </div>
        </x-ui.card>
    </div>

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
                    <x-ui.button wire:click="confirmArchive" variant="danger">Archive Campaign</x-ui.button>
                </div>

                <div class="border-t border-slate-100"></div>

                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-sm font-medium text-slate-900">Duplicate Campaign</h3>
                        <p class="text-xs text-slate-500">Create a new campaign with the same settings. The new campaign will be a draft.</p>
                    </div>
                    <x-ui.button wireClick="duplicate" variant="secondary">Duplicate</x-ui.button>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Archive Confirmation Modal --}}
    @if ($showArchiveModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" x-data>
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="$set('showArchiveModal', false)"></div>
            <div class="relative w-full max-w-md rounded-2xl bg-white shadow-xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-slate-900">Archive this campaign?</h2>
                    <button wire:click="$set('showArchiveModal', false)" class="text-slate-400 hover:text-slate-600">
                        <x-heroicon-o-x-mark class="size-5" />
                    </button>
                </div>
                <div class="px-6 py-5">
                    <p class="text-sm text-slate-600">This campaign will be hidden from your campaigns list. Any active elements linked to this campaign will stop showing on your website.</p>
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
