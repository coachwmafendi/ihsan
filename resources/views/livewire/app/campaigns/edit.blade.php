{{-- resources/views/livewire/app/campaigns/edit.blade.php --}}
<?php use App\Enums\DonationStatus; ?>
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
                <span>Created {{ myrTime($campaign->created_at, withLabel: false, format: 'M d, Y') }}</span>
                <span>·</span>
                <span>{{ $campaign->end_date ? 'Ends '.myrTime($campaign->end_date, withLabel: false, format: 'M d, Y') : 'No end date' }}</span>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200">
        <nav class="-mb-px flex space-x-6 overflow-x-auto sm:space-x-8" aria-label="Tabs">
            <button type="button"
                @click="tab = 'overview'"
                :class="tab === 'overview' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Overview
            </button>
            <button type="button"
                @click="tab = 'settings'"
                :class="tab === 'settings' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Settings
            </button>
            <button type="button"
                @click="tab = 'checkout'"
                :class="tab === 'checkout' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Checkout Modal
            </button>
            <button type="button"
                @click="$wire.campaign_page_enabled ? tab = 'campaign-page' : null"
                :class="tab === 'campaign-page' && $wire.campaign_page_enabled ? 'border-blue-600 text-blue-600' : ($wire.campaign_page_enabled ? 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' : 'border-transparent text-slate-400 cursor-not-allowed')"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors"
                {{ $campaign_page_enabled ? '' : 'disabled' }}>
                Campaign Page
                @if (! $campaign_page_enabled)
                    <span class="ml-1 rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-500">off</span>
                @endif
            </button>
            <button type="button"
                @click="tab = 'actions'"
                :class="tab === 'actions' ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                class="whitespace-nowrap border-b-2 px-1 py-4 text-sm font-medium transition-colors">
                Actions
            </button>
        </nav>
    </div>

    {{-- Overview Tab (read-only campaign summary) --}}
    <div x-show="tab === 'overview'" x-cloak class="space-y-6">
        {{-- Stats Strip --}}
        @php
            $approx = $this->hasApproximateRaisedTotals() ? '≈ ' : '';
            $bySource = $this->donationAmountsBySource();
            $totalRaised = $bySource['campaign_page']['amount']
                + $bySource['checkout_modal']['amount']
                + $bySource['virtual_terminal']['amount'];
            $activePlansCount = $campaign->subscriptions()->whereIn('status', ['active', 'trialing'])->count();
            $lastDonationAt = $campaign->donations()->latest()->first()?->created_at;
        @endphp
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <dl class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                <div>
                    <dt class="text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">Total raised</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-950 dark:text-white">{{ $approx }}MYR {{ number_format($totalRaised, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">Checkout Modal</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-950 dark:text-white">{{ $bySource['checkout_modal']['approximate'] ? '≈ ' : '' }}MYR {{ number_format($bySource['checkout_modal']['amount'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">Campaign Page</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-950 dark:text-white">{{ $bySource['campaign_page']['approximate'] ? '≈ ' : '' }}MYR {{ number_format($bySource['campaign_page']['amount'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">Virtual Terminal</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-950 dark:text-white">{{ $bySource['virtual_terminal']['approximate'] ? '≈ ' : '' }}MYR {{ number_format($bySource['virtual_terminal']['amount'], 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">Recurring plans</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-950 dark:text-white">{{ number_format($activePlansCount) }} active</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium whitespace-nowrap text-gray-500 dark:text-gray-400">Last donation</dt>
                    <dd class="mt-1 text-base font-semibold text-gray-950 dark:text-white">{{ $lastDonationAt ? myrTime($lastDonationAt, withLabel: false, format: 'M j, Y') : '—' }}</dd>
                </div>
            </dl>
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
                                    Target {{ $this->default_currency }} {{ number_format((float) $campaign->target_amount, 2) }}
                                @else
                                    No target
                                @endif
                                ·
                                @if ($campaign->has_end_date && $campaign->end_date)
                                    Ends {{ myrTime($campaign->end_date, withLabel: false, format: 'M d, Y') }}
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
                                    <x-ui.badge status="default" size="sm">Min {{ $this->default_currency }} {{ number_format((float) $campaign->minimum_amount, 2) }}</x-ui.badge>
                                @endif
                            </dd>
                        </div>

                        {{-- Checkout Defaults --}}
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Checkout Defaults</dt>
                            <dd class="mt-0.5 text-sm text-slate-900 leading-relaxed">
                                Default frequency: <strong>{{ ucfirst(str_replace('_', ' ', $this->default_frequency)) }}</strong><br>
                                Default amount: <strong>{{ $this->default_currency }} {{ number_format((float) ($this->default_amount ?? 50), 2) }}</strong><br>
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

                <x-ui.card title="Monthly Upsell" description="Offer one-time donors a monthly plan">
                    <x-slot:actions>
                        <button type="button" wire:click="editMonthlyUpsell" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                            Edit
                        </button>
                    </x-slot:actions>

                    <dl class="space-y-4">
                        <div class="flex items-center justify-between">
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
                            <dd>
                                <x-ui.badge status="{{ $upsell_enabled ? 'success' : 'default' }}" size="sm">
                                    {{ $upsell_enabled ? 'Enabled' : 'Disabled' }}
                                </x-ui.badge>
                            </dd>
                        </div>

                        @if ($upsell_enabled)
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Tiers</dt>
                                <dd class="mt-2 space-y-1.5">
                                    @forelse ($upsell_tiers as $tier)
                                        <div class="text-sm text-slate-700">
                                            {{ $default_currency }} {{ (int) $tier['min'] }}&ndash;{{ ($tier['max'] ?? null) === null ? 'no limit' : (int) $tier['max'] }}
                                            &rarr;
                                            {{ collect($tier['offers'] ?? [])->map(fn (array $offer): string => ($offer['type'] ?? 'percent') === 'fixed'
                                                ? $default_currency.' '.(int) $offer['value']
                                                : (int) $offer['value'].'%')->join(' & ') }}
                                        </div>
                                    @empty
                                        <div class="text-sm text-slate-500">No tiers configured yet.</div>
                                    @endforelse
                                </dd>
                            </div>

                            <div class="flex items-center justify-between">
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Decline cooldown</dt>
                                <dd class="text-sm text-slate-700">{{ $upsell_cooldown_days }} days</dd>
                            </div>

                            @php $upsellStats = $this->upsellStats(); @endphp

                            <div class="border-t border-slate-100 pt-4">
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Results</dt>

                                @if ($upsellStats['offers_shown'] === 0)
                                    <dd class="mt-2 text-sm text-slate-500">No offers shown yet.</dd>
                                @else
                                    <dd class="mt-3 grid grid-cols-2 gap-3">
                                        <div>
                                            <div class="text-lg font-semibold text-slate-900">{{ $upsellStats['offers_shown'] }}</div>
                                            <div class="text-xs text-slate-500">Offers shown</div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-slate-900">
                                                {{ $upsellStats['accepted'] }}@if ($upsellStats['shows_rate'])<span class="ml-1 text-sm font-normal text-slate-500">({{ round($upsellStats['accepted'] / $upsellStats['offers_shown'] * 100) }}%)</span>@endif
                                            </div>
                                            <div class="text-xs text-slate-500">Accepted</div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-slate-900">{{ $upsellStats['plans_started'] }}</div>
                                            <div class="text-xs text-slate-500">Plans started</div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-slate-900">
                                                {{ $upsellStats['is_approximate'] ? '≈ ' : '' }}MYR {{ number_format($upsellStats['added_monthly_value'], 2) }}
                                            </div>
                                            <div class="text-xs text-slate-500">Added per month</div>
                                        </div>
                                    </dd>
                                    <dd class="mt-3 text-xs text-slate-400">
                                        Counts donors who saw the offer and went on to pay. Donors who left before paying are not recorded.
                                    </dd>
                                @endif
                            </div>
                        @endif
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
                                            <p class="text-xs text-slate-500">{{ myrTime($donation->created_at) }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <x-donation-report-amount :donation="$donation" />
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
                <x-ui.card title="Campaign Page">
                    <x-slot:actions>
                        <button type="button" wire:click="$set('activeTab', '{{ $campaign_page_enabled ? 'campaign-page' : 'settings' }}')" class="text-sm font-medium text-teal-600 hover:text-teal-700">
                            {{ $campaign_page_enabled ? 'Edit Campaign Page' : 'Enable Campaign Page' }}
                        </button>
                    </x-slot:actions>

                    @if (! $campaign_page_enabled)
                        <div class="mb-4">
                            <x-ui.badge status="default" size="sm">Disabled</x-ui.badge>
                        </div>

                        <x-ui.empty-state
                            icon="heroicon-o-eye-slash"
                            title="Campaign Page is disabled"
                            description="Campaign Page is not enabled. Enable it in Settings > Campaign formats to configure this page."
                        />
                    @else
                        @php $campaignPageUrl = route('campaigns.public', $campaign->public_id); @endphp

                        <dl class="space-y-4">
                            {{-- Status --}}
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
                                <dd class="mt-1.5">
                                    <x-ui.badge status="success" size="sm">Enabled</x-ui.badge>
                                </dd>
                            </div>

                            {{-- Public URL --}}
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Public URL</dt>
                                <dd class="mt-1.5 flex items-center gap-2">
                                    <input
                                        type="text"
                                        readonly
                                        value="{{ $campaignPageUrl }}"
                                        class="block w-full min-w-0 rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-xs text-slate-600"
                                    />
                                    <x-ui.copy-button value="{{ $campaignPageUrl }}" title="Copy URL" />
                                    <a
                                        href="{{ $campaignPageUrl }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        title="Open in new tab"
                                        class="inline-flex items-center text-slate-400 transition hover:text-slate-700 shrink-0"
                                    >
                                        <x-heroicon-o-arrow-top-right-on-square class="size-4 shrink-0" />
                                    </a>
                                </dd>
                            </div>

                            {{-- Content Title --}}
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Content Title</dt>
                                <dd class="mt-0.5 text-sm text-slate-900">
                                    {{ $contentTitle }}
                                    @if (empty($campaign->config['content_title']))
                                        <span class="text-xs text-slate-400">(fallback)</span>
                                    @endif
                                </dd>
                            </div>

                            {{-- Content Message --}}
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Content Message</dt>
                                <dd class="mt-0.5 text-sm text-slate-900 leading-relaxed whitespace-pre-wrap">
                                    @if (empty($campaign->config['content_message']))
                                        Not set
                                    @else
                                        {{ \Illuminate\Support\Str::limit($contentMessage, 150) }}
                                    @endif
                                </dd>
                            </div>

                            {{-- Show Total Raised --}}
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Show Total Raised</dt>
                                <dd class="mt-1.5">
                                    <x-ui.badge status="{{ $show_total_raised ? 'success' : 'default' }}" size="sm">
                                        {{ $show_total_raised ? 'On' : 'Off' }}
                                    </x-ui.badge>
                                </dd>
                            </div>

                            {{-- Post-Donation Experience --}}
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Post-Donation Experience</dt>
                                <dd class="mt-0.5 text-sm text-slate-900 leading-relaxed">
                                    @if ($postDonationMode === 'redirect')
                                        Redirect to URL
                                        @if ($redirect_url)
                                            <br><span class="text-xs text-slate-500 break-all">{{ $redirect_url }}</span>
                                        @endif
                                    @else
                                        Default thank-you screen
                                    @endif
                                </dd>
                            </div>

                            {{-- Thank-You Message --}}
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Thank-You Message</dt>
                                <dd class="mt-0.5 text-sm text-slate-900">
                                    {{ $thank_you_message ? 'Set' : 'Not set' }}
                                </dd>
                            </div>

                            {{-- Sharing Channels --}}
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Sharing Channels</dt>
                                <dd class="mt-1.5 flex flex-wrap gap-2">
                                    @if (empty($shareChannels))
                                        <span class="text-sm text-slate-500">None</span>
                                    @else
                                        @php
                                            $channelLabels = [
                                                'facebook' => 'Facebook',
                                                'x' => 'X',
                                                'linkedin' => 'LinkedIn',
                                                'email' => 'Email',
                                            ];
                                        @endphp
                                        @foreach ($shareChannels as $channel)
                                            <x-ui.badge status="default" size="sm">{{ $channelLabels[$channel] ?? ucfirst($channel) }}</x-ui.badge>
                                        @endforeach
                                    @endif
                                </dd>
                            </div>

                            {{-- Sharing Message --}}
                            <div>
                                <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Default Sharing Message</dt>
                                <dd class="mt-0.5 text-sm text-slate-900 leading-relaxed">
                                    {{ $shareMessage ? \Illuminate\Support\Str::limit($shareMessage, 150) : 'Not set' }}
                                </dd>
                            </div>
                        </dl>
                    @endif
                </x-ui.card>

                <x-ui.card title="Linked Elements" description="Embed elements using this campaign">
                    @php $elements = $campaign->elements()->where('is_active', true)->select(['id','public_id','token','name','type','created_at'])->get(); @endphp
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
                            title="No active elements"
                            description="Only active embed elements are shown here."
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

                    <div x-data="{ wordCount: 0 }" x-init="wordCount = ($wire.description ?? '').trim() === '' ? 0 : ($wire.description ?? '').trim().split(/\s+/).filter(w => w.length > 0).length">
                        <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
                        <textarea
                            id="description"
                            wire:model.live.debounce.150ms="description"
                            x-on:input="wordCount = $event.target.value.trim() === '' ? 0 : $event.target.value.trim().split(/\s+/).filter(w => w.length > 0).length"
                            rows="3"
                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            placeholder="Describe the purpose of this campaign..."
                        ></textarea>
                        <div class="mt-1 flex items-start justify-between gap-4">
                            @error('description') <p class="text-xs text-red-600">{{ $message }}</p> @else <span></span> @enderror
                            <p class="shrink-0 text-xs" :class="wordCount > 200 ? 'text-red-600' : 'text-slate-500'">
                                <span x-text="wordCount"></span> / 200 words
                            </p>
                        </div>
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
                                    accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/avif"
                                    class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100"
                                />
                                <p class="text-xs text-slate-500">Allowed: JPG, PNG, GIF, WebP, AVIF. Max 5 MB.</p>
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

            <x-ui.card title="Campaign formats" description="Choose how supporters can access this campaign.">
                <div class="space-y-4">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" disabled checked class="mt-0.5 size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600 opacity-70">
                        <span class="text-sm font-semibold text-slate-900">Checkout Modal</span>
                        <span class="ml-auto inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">Always on</span>
                    </label>
                    <p class="text-sm text-slate-500 ml-7">Launches as an overlay on your website. This is the default way to collect donations.</p>

                    <div class="border-t border-slate-100"></div>

                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" wire:model="campaign_page_enabled" class="mt-0.5 size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                        <span class="text-sm font-semibold text-slate-900">Campaign Page</span>
                    </label>
                    <p class="text-sm text-slate-500 ml-7">Launches as a separate donation page with an integrated checkout form. Enable this if this campaign will receive traffic from external sources, such as marketing communications.</p>
                </div>
            </x-ui.card>

            <x-ui.card title="Payment Processor" description="Choose the payment processor donors will use for this campaign.">
                <div class="space-y-4">
                    <div class="max-w-md">
                        <label for="payment_gateway" class="block text-sm font-medium text-slate-700">Processor</label>
                        <x-ui.select id="payment_gateway" wire:model="payment_gateway" class="mt-1.5 block w-full">
                            <flux:select.option value="stripe">Stripe</flux:select.option>
                            <flux:select.option value="chip">CHIP</flux:select.option>
                        </x-ui.select>
                        @error('payment_gateway') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    @if ($payment_gateway === 'chip' && ! $campaign->organization?->chip_onboarded)
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Your organization has not completed CHIP setup. Go to Settings > Payment Processors to add your CHIP credentials.
                        </div>
                    @endif
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
                                wire:click="$set('checkoutPanel', 'upsell')"
                                class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $checkoutPanel === 'upsell' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                            >
                                <x-heroicon-o-arrow-trending-up class="size-5" />
                                Monthly Upsell
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
                                                    <span>{{ $this->activeCurrency }}</span>
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
                                                    <span>{{ $this->activeCurrency }}</span>
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
                                    <label for="default_amount" class="block text-sm font-medium text-slate-700">Default Amount ({{ $this->activeCurrency }})</label>
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
                                    <label for="minimum_amount" class="block text-sm font-medium text-slate-700">Minimum Amount ({{ $this->activeCurrency }})</label>
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

                        @if ($checkoutPanel === 'upsell')
                            <div class="space-y-4">
                                <div class="rounded-lg bg-slate-50 p-4">
                                    <div class="flex items-start gap-2 text-sm text-slate-700">
                                        <x-heroicon-o-information-circle class="mt-0.5 size-5 shrink-0 text-slate-400" />
                                        <div class="space-y-2">
                                            <p>
                                                After a donor picks a one-time amount and taps Continue, they see one extra screen
                                                asking whether they would like to give that amount every month instead.
                                            </p>
                                            <p>
                                                They always get two choices: <strong>their own amount monthly</strong>, and a lighter
                                                amount you set below. Declining takes them straight to checkout with the one-time
                                                gift they already chose &mdash; nothing is lost.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <label class="flex items-start gap-3">
                                    <input type="checkbox" wire:model.live="upsell_enabled" class="mt-0.5 size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                                    <span>
                                        <span class="block text-sm font-medium text-slate-700">Offer a monthly plan to one-time donors</span>
                                        <span class="block text-sm text-slate-500">Shown after the donor picks an amount, before they enter their details.</span>
                                    </span>
                                </label>

                                @if ($upsell_enabled)
                                    <div class="max-w-xs">
                                        <label for="upsell_cooldown_days" class="block text-sm font-medium text-slate-700">Decline cooldown (days)</label>
                                        <input
                                            type="text"
                                            inputmode="numeric"
                                            maxlength="3"
                                            pattern="[0-9]*"
                                            id="upsell_cooldown_days"
                                            wire:model="upsell_cooldown_days"
                                            x-on:input="$event.target.value = $event.target.value.replace(/\D/g, '').substring(0, 3)"
                                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                            placeholder="30"
                                        />
                                        <p class="mt-1 text-xs text-slate-500">If a donor declines, we will not ask again on that device for this many days.</p>
                                    </div>

                                    <div class="space-y-3">
                                        <div>
                                            <span class="text-base font-medium text-slate-700">Tiers</span>
                                            <p class="text-xs text-slate-500">
                                                Each tier covers a range of one-time amounts and sets the lighter monthly option
                                                shown beside the donor's own amount. Ranges cannot overlap.
                                            </p>
                                        </div>

                                        @foreach ($upsell_tiers as $index => $tier)
                                            <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-sm font-semibold text-slate-700">Tier {{ $index + 1 }}</span>
                                                    <button type="button" wire:click="removeUpsellTier({{ $index }})" class="text-sm font-medium text-red-600 hover:text-red-700">
                                                        Remove
                                                    </button>
                                                </div>

                                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-600">One-time from ({{ $default_currency }})</label>
                                                        <input
                                                            type="text"
                                                            inputmode="numeric"
                                                            wire:model.live.debounce.400ms="upsell_tiers.{{ $index }}.min"
                                                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                                        />
                                                        <p class="mt-1 text-xs text-slate-500">Smallest one-time gift this tier covers.</p>
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-medium text-slate-600">One-time up to ({{ $default_currency }})</label>
                                                        <input
                                                            type="text"
                                                            inputmode="numeric"
                                                            wire:model.live.debounce.400ms="upsell_tiers.{{ $index }}.max"
                                                            placeholder="No limit"
                                                            class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                                        />
                                                        <p class="mt-1 text-xs text-slate-500">Leave empty for no upper limit.</p>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                                    @foreach ($tier['offers'] ?? [] as $offerIndex => $offer)
                                                        <div class="flex items-end gap-2">
                                                            <div class="flex-1">
                                                                <label class="block text-xs font-medium text-slate-600">Offer {{ $offerIndex + 1 }}</label>
                                                                <input
                                                                    type="text"
                                                                    inputmode="numeric"
                                                                    wire:model.live.debounce.400ms="upsell_tiers.{{ $index }}.offers.{{ $offerIndex }}.value"
                                                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                                                />
                                                            </div>
                                                            <x-ui.select wire:model.live="upsell_tiers.{{ $index }}.offers.{{ $offerIndex }}.type" class="w-28">
                                                                <flux:select.option value="percent">% of gift</flux:select.option>
                                                                <flux:select.option value="fixed">{{ $default_currency }} fixed</flux:select.option>
                                                            </x-ui.select>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <p class="text-xs text-slate-500">
                                                    <strong>% of gift</strong> takes a share of what the donor was about to give.
                                                    <strong>{{ $default_currency }} fixed</strong> is the same amount every time.
                                                    Either way the result is rounded to the nearest 5, and the highest one that
                                                    lands below the donor's own amount becomes their lighter option.
                                                </p>

                                                @php $tierPreview = $this->upsellTierPreview($index); @endphp

                                                @if ($tierPreview !== [])
                                                    <div class="rounded-lg border border-slate-200 bg-white p-3">
                                                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">What donors would see</p>
                                                        <dl class="mt-2 space-y-1">
                                                            @foreach ($tierPreview as $row)
                                                                <div class="flex flex-wrap items-baseline gap-x-2 text-sm">
                                                                    <dt class="text-slate-500">{{ \App\Support\Currency::formatCompact($default_currency, $row['amount']) }} one-time</dt>
                                                                    <dd class="font-medium text-slate-800">
                                                                        &rarr;
                                                                        {{ collect($row['offers'])->map(fn (float $value): string => \App\Support\Currency::formatCompact($default_currency, $value).'/month')->join(' or ') }}
                                                                        @if (count($row['offers']) < 2)
                                                                            <span class="font-normal text-slate-500">only</span>
                                                                        @endif
                                                                    </dd>
                                                                </div>
                                                            @endforeach
                                                        </dl>

                                                        @php $unusedOffers = $this->upsellUnusedOffers($index); @endphp

                                                        @if ($unusedOffers !== [])
                                                            <p class="mt-2 text-xs text-amber-700">
                                                                {{ collect($unusedOffers)->join(' and ') }} {{ count($unusedOffers) > 1 ? 'are' : 'is' }} never used:
                                                                only the highest offer below the donor's own amount becomes the lighter button,
                                                                so the larger value always wins. Remove it, or split the values across separate tiers.
                                                            </p>
                                                        @endif

                                                        @if (collect($tierPreview)->contains(fn (array $row): bool => count($row['offers']) < 2))
                                                            <p class="mt-2 text-xs text-amber-700">
                                                                Some amounts get no lighter option, so those donors see one button only.
                                                                Raise the offer value, or lower the campaign minimum amount.
                                                            </p>
                                                        @endif
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach

                                        @error('upsell_tiers')
                                            <p class="text-sm text-red-600">{{ $message }}</p>
                                        @enderror

                                        @if (count($upsell_tiers) < 6)
                                            <x-ui.button type="button" wire:click="addUpsellTier" variant="ghost">Add tier</x-ui.button>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 gap-3">
                                            <div>
                                                <label for="upsell_heading" class="block text-sm font-medium text-slate-700">Heading override</label>
                                                <input
                                                    type="text"
                                                    id="upsell_heading"
                                                    wire:model="upsell_heading"
                                                    placeholder="Become a monthly supporter"
                                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                                />
                                            </div>
                                            <div>
                                                <label for="upsell_body" class="block text-sm font-medium text-slate-700">Message override</label>
                                                <textarea
                                                    id="upsell_body"
                                                    wire:model="upsell_body"
                                                    rows="3"
                                                    placeholder="Use :amount for the one-time amount."
                                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                                ></textarea>
                                                <p class="mt-1 text-xs text-slate-500">Leave empty to use the default wording. Write <code>:amount</code> where the donor's one-time amount should appear.</p>
                                            </div>
                                            <div>
                                                <label for="upsell_decline_label" class="block text-sm font-medium text-slate-700">Decline link override</label>
                                                <input
                                                    type="text"
                                                    id="upsell_decline_label"
                                                    wire:model="upsell_decline_label"
                                                    placeholder="No, keep my one-time :amount gift"
                                                    class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                                />
                                                <p class="mt-1 text-xs text-slate-500">The wording donors tap to keep their one-time gift. <code>:amount</code> works here too.</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                    <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                                    <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
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
    <div x-show="tab === 'campaign-page'" x-cloak class="relative space-y-6">
        @if (! $campaign_page_enabled)
            <div class="absolute inset-0 z-10 flex items-start justify-center rounded-xl bg-white/90 pt-20">
                <x-ui.empty-state
                    icon="heroicon-o-eye-slash"
                    title="Campaign Page is disabled"
                    description="Enable Campaign Page in Settings > Campaign formats to configure this page."
                />
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4 {{ $campaign_page_enabled ? '' : 'pointer-events-none opacity-50' }}">
            {{-- Left sidebar --}}
            <div class="lg:col-span-1">
                <x-ui.card>
                    <nav class="flex flex-col space-y-1" aria-label="Campaign page sections">
                        <button type="button"
                            wire:click="$set('campaignPagePanel', 'content')"
                            class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $campaignPagePanel === 'content' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                        >
                            <x-heroicon-o-document-text class="size-5" />
                            Content
                        </button>
                        <button type="button"
                            wire:click="$set('campaignPagePanel', 'progress')"
                            class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $campaignPagePanel === 'progress' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                        >
                            <x-heroicon-o-chart-bar class="size-5" />
                            Campaign progress
                        </button>
                        <button type="button"
                            wire:click="$set('campaignPagePanel', 'thank-you')"
                            class="inline-flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-medium transition-colors {{ $campaignPagePanel === 'thank-you' ? 'bg-teal-50 text-teal-700' : 'text-slate-600 hover:bg-slate-50' }}"
                        >
                            <x-heroicon-o-heart class="size-5" />
                            Thank you screen
                        </button>
                    </nav>
                </x-ui.card>
            </div>

            {{-- Right panel --}}
            <div class="lg:col-span-3">
                @if ($campaignPagePanel === 'thank-you')
                    <x-ui.card title="Thank you screen" description="Choose what to show supporters after they donate.">
                        <div class="space-y-6">
                            {{-- Post-donation mode --}}
                            <div class="space-y-3">
                                <span class="block text-sm font-medium text-slate-900">Post-donation experience</span>

                                <label class="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="radio"
                                        wire:model.live="postDonationMode"
                                        value="default"
                                        class="mt-0.5 h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-600"
                                    />
                                    <span class="text-sm font-semibold text-slate-900">Show supporters the default thank you screen</span>
                                </label>
                                <p class="ml-7 text-sm text-slate-500">Display a thank-you message on the campaign page after a successful donation.</p>

                                <label class="flex cursor-pointer items-start gap-3">
                                    <input
                                        type="radio"
                                        wire:model.live="postDonationMode"
                                        value="redirect"
                                        class="mt-0.5 h-4 w-4 border-slate-300 text-teal-600 focus:ring-teal-600"
                                    />
                                    <span class="text-sm font-semibold text-slate-900">Redirect supporters to a specific URL</span>
                                </label>
                                <p class="ml-7 text-sm text-slate-500">Send donors to an external thank-you page instead.</p>

                                @if ($postDonationMode === 'default')
                                    <div class="ml-7">
                                        <label for="thank_you_message" class="block text-sm font-medium text-slate-700">Thank-you message</label>
                                        <textarea
                                            id="thank_you_message"
                                            wire:model="thank_you_message"
                                            rows="3"
                                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                            placeholder="With the contributions we have received, we are closer to our goal. Thank you for making a difference through your generosity."
                                        ></textarea>
                                        @error('thank_you_message') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif

                                @if ($postDonationMode === 'redirect')
                                    <div class="ml-7">
                                        <label for="redirect_url" class="block text-sm font-medium text-slate-700">Redirect URL</label>
                                        <input
                                            type="url"
                                            id="redirect_url"
                                            wire:model="redirect_url"
                                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                            placeholder="https://example.com/thank-you"
                                        />
                                        @error('redirect_url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>

                            @if ($postDonationMode === 'default')
                                <div class="border-t border-slate-100"></div>

                                {{-- Sharing --}}
                                <div class="space-y-4">
                                    <div>
                                        <span class="block text-sm font-medium text-slate-900">Sharing channels</span>
                                        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    wire:model="shareChannels"
                                                    value="facebook"
                                                    class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                                />
                                                <span class="text-sm text-slate-700">Facebook</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    wire:model="shareChannels"
                                                    value="x"
                                                    class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                                />
                                                <span class="text-sm text-slate-700">X</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    wire:model="shareChannels"
                                                    value="linkedin"
                                                    class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                                />
                                                <span class="text-sm text-slate-700">LinkedIn</span>
                                            </label>
                                            <label class="flex cursor-pointer items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    wire:model="shareChannels"
                                                    value="email"
                                                    class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600"
                                                />
                                                <span class="text-sm text-slate-700">Email</span>
                                            </label>
                                        </div>
                                    </div>

                                    @php $campaignPageUrl = route('campaigns.public', $campaign->public_id); @endphp
                                    <div>
                                        <label for="campaign_page_url" class="block text-sm font-medium text-slate-700">Sharing URL</label>
                                        <div class="mt-1.5 flex items-center gap-2">
                                            <input
                                                id="campaign_page_url"
                                                type="text"
                                                readonly
                                                value="{{ $campaignPageUrl }}"
                                                class="block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-600"
                                            />
                                            <x-ui.copy-button value="{{ $campaignPageUrl }}" title="Copy URL" />
                                        </div>
                                    </div>

                                    <div>
                                        <label for="share_message" class="block text-sm font-medium text-slate-700">Default sharing message</label>
                                        <textarea
                                            id="share_message"
                                            wire:model="shareMessage"
                                            rows="3"
                                            maxlength="280"
                                            class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        ></textarea>
                                        <div class="mt-1 flex items-center justify-between">
                                            @error('shareMessage') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                            <p class="ml-auto text-xs text-slate-500">{{ strlen($shareMessage ?? '') }}/280</p>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                                <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                                <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                            </div>
                        </div>
                    </x-ui.card>
                @elseif ($campaignPagePanel === 'content')
                    <x-ui.card title="Content" description="Customize what visitors see on the public campaign page.">
                        <div class="space-y-6">
                            {{-- Logo --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Logo</label>
                                <div class="mt-2 flex items-start gap-4">
                                    @if ($contentLogo)
                                        <img src="{{ $contentLogo->temporaryUrl() }}" alt="New logo preview" class="h-16 rounded-lg border border-slate-200 object-contain">
                                    @elseif ($existingContentLogo)
                                        <img src="{{ Storage::disk('public')->url($existingContentLogo) }}" alt="Current logo" class="h-16 rounded-lg border border-slate-200 object-contain">
                                    @elseif ($campaign->organization->logo_path)
                                        <img src="{{ $campaign->organization->logoUrl() }}" alt="Organization logo" class="h-16 rounded-lg border border-slate-200 object-contain">
                                    @endif
                                    <div class="flex-1">
                                        <input type="file" id="content_logo" wire:model="contentLogo" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/avif" class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100">
                                        <p class="mt-1 text-xs text-slate-500">Leave empty to use the organization logo. Allowed: JPG, PNG, GIF, WebP, AVIF. Max 5 MB.</p>
                                        <div wire:loading wire:target="contentLogo" class="mt-2 text-sm text-slate-500">Uploading...</div>
                                        @error('contentLogo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Image --}}
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Image</label>
                                <div class="mt-2 flex items-start gap-4">
                                    @if ($contentImage)
                                        <img src="{{ $contentImage->temporaryUrl() }}" alt="New image preview" class="h-24 w-40 rounded-lg border border-slate-200 object-cover">
                                    @elseif ($existingContentImage)
                                        <img src="{{ Storage::disk('public')->url($existingContentImage) }}" alt="Current image" class="h-24 w-40 rounded-lg border border-slate-200 object-cover">
                                    @elseif ($existing_image)
                                        <img src="{{ Storage::disk('public')->url($existing_image) }}" alt="Campaign image" class="h-24 w-40 rounded-lg border border-slate-200 object-cover">
                                    @endif
                                    <div class="flex-1">
                                        <input type="file" id="content_image" wire:model="contentImage" accept="image/png,image/jpeg,image/jpg,image/gif,image/webp,image/avif" class="block w-full text-sm text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-teal-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-teal-700 hover:file:bg-teal-100">
                                        <p class="mt-1 text-xs text-slate-500">Leave empty to use the main campaign image. Allowed: JPG, PNG, GIF, WebP, AVIF. Max 5 MB.</p>
                                        <div wire:loading wire:target="contentImage" class="mt-2 text-sm text-slate-500">Uploading...</div>
                                        @error('contentImage') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                </div>
                            </div>

                            {{-- Title --}}
                            <div>
                                <label for="content_title" class="block text-sm font-medium text-slate-700">Title</label>
                                <input type="text" id="content_title" wire:model="contentTitle" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" placeholder="{{ $campaign->title }}">
                                @error('contentTitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>

                            {{-- Message --}}
                            <div>
                                <label for="content_message" class="block text-sm font-medium text-slate-700">Message</label>
                                <textarea id="content_message" wire:model="contentMessage" rows="5" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"></textarea>
                                <div class="mt-1 flex items-center justify-between">
                                    @error('contentMessage') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                                    <p class="ml-auto text-xs text-slate-500">{{ str_word_count($contentMessage ?? '') }}/200 words</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                            <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                            <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                        </div>
                    </x-ui.card>
                @elseif ($campaignPagePanel === 'progress')
                    <x-ui.card title="Campaign progress" description="Choose what to show about your campaign’s progress.">
                        <div class="space-y-6">
                            {{-- Show total raised amount --}}
                            <label class="flex cursor-pointer items-start gap-3">
                                <input type="checkbox" wire:model="show_total_raised" class="mt-0.5 size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                                <span class="text-sm font-semibold text-slate-900">Show total raised amount</span>
                            </label>

                            <div class="border-t border-slate-100"></div>

                            {{-- Campaign goal --}}
                            <div class="space-y-3">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="checkbox" wire:model.live="has_target" class="mt-0.5 size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                                    <span class="text-sm font-semibold text-slate-900">Add campaign goal</span>
                                </label>

                                @if ($has_target)
                                    <div class="ml-7">
                                        <label for="target_amount" class="block text-sm font-medium text-slate-700">Goal amount</label>
                                        <input type="number" id="target_amount" wire:model="target_amount" min="0" max="9999999" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500" placeholder="10000">
                                        @error('target_amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>

                            <div class="border-t border-slate-100"></div>

                            {{-- End date --}}
                            <div class="space-y-3">
                                <label class="flex cursor-pointer items-start gap-3">
                                    <input type="checkbox" wire:model.live="has_end_date" class="mt-0.5 size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600">
                                    <span class="text-sm font-semibold text-slate-900">Set end date</span>
                                </label>

                                @if ($has_end_date)
                                    <div class="ml-7">
                                        <label for="end_date" class="block text-sm font-medium text-slate-700">End date</label>
                                        <input type="date" id="end_date" wire:model="end_date" class="mt-1.5 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                                        @error('end_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4">
                            <x-ui.button href="{{ route('app.campaigns.index') }}" variant="ghost">Cancel</x-ui.button>
                            <x-ui.button type="button" wire:click="save" variant="primary">Save Changes</x-ui.button>
                        </div>
                    </x-ui.card>
                @else
                    <x-ui.card>
                        <x-ui.empty-state
                            icon="heroicon-o-cube"
                            title="Section not available"
                            description="Choose a section from the sidebar to edit."
                        />
                    </x-ui.card>
                @endif
            </div>
        </div>
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
