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
                    <button
                        type="button"
                        x-on:click="navigator.clipboard.writeText('{{ $campaign->public_id }}'); $dispatch('notify', { message: 'Campaign ID copied', variant: 'success' })"
                        class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus:ring-2 focus:ring-teal-500"
                        title="Copy ID"
                    >
                        <x-heroicon-o-clipboard-document class="size-4" />
                    </button>
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
                @if ($campaign->description)
                    <x-ui.card title="About">
                        <p class="text-sm text-slate-600 leading-relaxed">{{ $campaign->description }}</p>
                    </x-ui.card>
                @endif

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
                <x-ui.card title="Details">
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Campaign ID</dt>
                            <dd class="mt-0.5 text-sm text-slate-900 font-mono">{{ $campaign->public_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Form Parameter</dt>
                            <dd class="mt-0.5 text-sm text-slate-900 font-mono">{{ $campaign->form_parameter }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Recurring</dt>
                            <dd class="mt-0.5 text-sm text-slate-900">{{ $campaign->allow_recurring ? 'Allowed' : 'Not allowed' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Custom Amount</dt>
                            <dd class="mt-0.5 text-sm text-slate-900">{{ $campaign->allow_custom_amount ? 'Allowed' : 'Not allowed' }}</dd>
                        </div>
                        @if ($campaign->minimum_amount)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Minimum Amount</dt>
                                <dd class="mt-0.5 text-sm text-slate-900">{{ $this->getCurrencySymbol() }} {{ number_format((float) $campaign->minimum_amount, 2) }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card>

                <x-ui.card title="Linked Elements" description="Embed elements using this campaign">
                    @php $elements = $campaign->elements()->select(['id','public_id','token','name','type','created_at'])->get(); @endphp
                    @if ($elements->isNotEmpty())
                        <div class="divide-y divide-slate-100">
                            @foreach ($elements as $element)
                                <div class="flex items-center justify-between py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-slate-50">
                                            <x-heroicon-o-code-bracket class="size-4 text-slate-400" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium text-slate-900">{{ $element->name }}</p>
                                            <p class="text-xs text-slate-500">{{ ucfirst($element->type->value) }}</p>
                                        </div>
                                    </div>
                                    <code class="rounded bg-slate-50 px-2 py-0.5 text-xs text-slate-600 font-mono">{{ $element->public_id }}</code>
                                </div>
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
                            @if ($existing_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($existing_image))
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
                                @if ($existing_image && \Illuminate\Support\Facades\Storage::disk('public')->exists($existing_image))
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
                                type="text"
                                inputmode="numeric"
                                maxlength="5"
                                pattern="[0-9]*"
                                id="target_amount"
                                wire:model="target_amount"
                                x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').substring(0, 5)"
                                class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                placeholder="10000"
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
                                    <div class="relative mt-2">
                                        <select
                                            id="default_currency"
                                            wire:model="default_currency"
                                            class="block w-full appearance-none rounded-lg border border-slate-300 bg-white px-4 py-3 pr-10 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        >
                                            @foreach ($acceptedCurrencies as $currency)
                                                <option value="{{ $currency }}">
                                                    {{ $currency }} · {{ match($currency) {
                                                        'MYR' => 'Malaysian Ringgit',
                                                        'USD' => 'United States Dollar',
                                                        'SGD' => 'Singapore Dollar',
                                                        'GBP' => 'British Pound',
                                                        'EUR' => 'Euro',
                                                        'AUD' => 'Australian Dollar',
                                                        default => $currency,
                                                    } }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                                            <x-heroicon-o-chevron-down class="size-4" />
                                        </div>
                                    </div>
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
                                    <select
                                        id="default_frequency"
                                        wire:model="default_frequency"
                                        class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    >
                                        <option value="one_time">One-time</option>
                                        <option value="monthly">Monthly</option>
                                    </select>
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
                                                        class="w-20 border-none bg-transparent p-0 text-lg font-semibold text-teal-700 focus:ring-0"
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
                                                        class="w-20 border-none bg-transparent p-0 text-lg font-semibold text-teal-700 focus:ring-0"
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
                                        wire:click="$toggle('allow_cover_fee')"
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
                                        <span>Platform processing fee is <strong>{{ number_format((float) config('services.stripe.processing_fee_percent', 2.5), 1) }}%</strong> plus a fixed fee per transaction.</span>
                                    </div>
                                </div>

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
