<div class="space-y-6">

    {{-- Page Header --}}
    <x-ui.page-header title="Tracking & Analytics">
        <x-slot:subtitle>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-slate-500">
                    <li>Settings</li>
                    <li>
                        <svg class="mx-1 h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </li>
                    <li class="font-medium text-slate-900">Tracking & Analytics</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>

    <p class="text-sm text-slate-500 -mt-4">
        Connect advertising and analytics platforms to measure donations and campaign performance.
        Settings apply to all donation forms, widgets, and checkout pages for your organization.
    </p>

    {{-- Provider Status Overview --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        @foreach ($this->configurations as $config)
            @php
                $status = $config->status;
                $provider = $config->provider;
            @endphp
            <div class="rounded-xl border border-slate-200 bg-white p-4">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-100 bg-slate-50">
                        @include('livewire.app.settings.tracking-provider-icon', ['provider' => $provider])
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $status->badgeClass() }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $status->dotClass() }}"></span>
                        {{ $status->label() }}
                    </span>
                </div>
                <p class="mt-3 text-sm font-medium text-slate-900 leading-tight">{{ $provider->label() }}</p>
                <p class="mt-0.5 text-xs text-slate-400">
                    @if ($config->last_event_at)
                        Last event {{ $config->last_event_at->diffForHumans() }}
                    @else
                        No events recorded
                    @endif
                </p>
            </div>
        @endforeach
    </div>

    {{-- Provider Config Cards (data-driven) --}}
    @foreach ($providers as $provider)
        @php
            $slug = $provider->value;
            $config = collect($this->configurations)->first(fn ($c) => $c->provider === $provider);
            $status = $config?->status ?? \App\Enums\TrackingProviderStatus::NotConfigured;
            $credFields = $provider->credentialFields();
            $optFields = $provider->optionFields();
        @endphp

        <x-ui.card>
            <x-slot:title>
                <div class="flex items-center gap-2.5">
                    <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md bg-slate-50 border border-slate-100">
                        @include('livewire.app.settings.tracking-provider-icon', ['provider' => $provider, 'size' => 'sm'])
                    </div>
                    {{ $provider->label() }}
                </div>
            </x-slot:title>
            <x-slot:description>{{ $provider->description() }}</x-slot:description>
            <x-slot:actions>
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $status->badgeClass() }}">
                    <span class="h-1.5 w-1.5 rounded-full {{ $status->dotClass() }}"></span>
                    {{ $status->label() }}
                </span>
            </x-slot:actions>

            <div class="space-y-5">
                {{-- Credential Fields --}}
                <div class="grid gap-4 {{ count($credFields) > 1 ? 'sm:grid-cols-2' : 'max-w-sm' }}">
                    @foreach ($credFields as $field)
                        <div class="space-y-1.5">
                            <label class="text-sm font-medium text-slate-700">{{ $field['label'] }}</label>
                            <input
                                type="{{ $field['type'] }}"
                                wire:model="credentials.{{ $slug }}.{{ $field['key'] }}"
                                placeholder="{{ $field['placeholder'] }}"
                                class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            />
                            @if (!empty($field['hint']))
                                <p class="text-xs text-slate-400">{{ $field['hint'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Option Toggles --}}
                @if (count($optFields) > 0)
                    <div class="rounded-lg border border-slate-100 bg-slate-50/60 divide-y divide-slate-100">
                        @foreach ($optFields as $opt)
                            <label class="flex cursor-pointer items-center justify-between px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ $opt['label'] }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ $opt['description'] }}</p>
                                </div>
                                <input
                                    type="checkbox"
                                    wire:model="options.{{ $slug }}.{{ $opt['key'] }}"
                                    class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500"
                                />
                            </label>
                        @endforeach
                    </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-between border-t border-slate-100 pt-4">
                    <x-ui.button variant="outline" size="sm" wireClick="testConnection('{{ $slug }}')">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Test Connection
                    </x-ui.button>
                    <x-ui.button variant="primary" wireClick="saveProvider('{{ $slug }}')">
                        Save Changes
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>
    @endforeach

    {{-- Advanced Tracking --}}
    <div class="rounded-xl border border-slate-200 bg-white overflow-hidden">
        <button
            type="button"
            wire:click="$toggle('showAdvanced')"
            class="flex w-full items-center justify-between px-5 py-4 text-left transition hover:bg-slate-50/60"
        >
            <div class="flex items-center gap-3">
                <div class="flex h-7 w-7 items-center justify-center rounded-md bg-violet-50">
                    <svg class="h-4 w-4 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-slate-900">Advanced Tracking</p>
                    <p class="text-xs text-slate-500 mt-0.5">Attribution window and parameter capture settings.</p>
                </div>
            </div>
            <svg class="h-4 w-4 text-slate-400 transition-transform {{ $showAdvanced ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>

        @if ($showAdvanced)
            <div class="border-t border-slate-100 p-5 space-y-6">
                {{-- Attribution Window --}}
                <div>
                    <p class="text-sm font-medium text-slate-900 mb-1">Attribution Window</p>
                    <p class="text-xs text-slate-500 mb-3">How far back to credit a campaign click for a donation.</p>
                    <div class="flex gap-2">
                        @foreach (['30' => '30 Days', '60' => '60 Days', '90' => '90 Days'] as $value => $label)
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="attributionWindow" value="{{ $value }}" class="sr-only peer" />
                                <span class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 transition peer-checked:border-teal-500 peer-checked:bg-teal-50 peer-checked:text-teal-700 hover:border-slate-300 hover:bg-slate-50">
                                    {{ $label }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- Captured Parameters --}}
                <div>
                    <p class="text-sm font-medium text-slate-900 mb-1">Captured Parameters</p>
                    <p class="text-xs text-slate-500 mb-3">URL parameters stored with each donation for attribution reporting.</p>
                    @php
                        $params = [
                            ['model' => 'captureUtmSource', 'label' => 'UTM Source', 'desc' => 'utm_source'],
                            ['model' => 'captureUtmMedium', 'label' => 'UTM Medium', 'desc' => 'utm_medium'],
                            ['model' => 'captureUtmCampaign', 'label' => 'UTM Campaign', 'desc' => 'utm_campaign'],
                            ['model' => 'captureUtmContent', 'label' => 'UTM Content', 'desc' => 'utm_content'],
                            ['model' => 'captureUtmTerm', 'label' => 'UTM Term', 'desc' => 'utm_term'],
                            ['model' => 'captureFbclid', 'label' => 'FBCLID', 'desc' => 'Facebook click ID'],
                            ['model' => 'captureGclid', 'label' => 'GCLID', 'desc' => 'Google click ID'],
                            ['model' => 'captureTtclid', 'label' => 'TTCLID', 'desc' => 'TikTok click ID'],
                            ['model' => 'captureReferrer', 'label' => 'Referrer URL', 'desc' => 'HTTP referrer'],
                            ['model' => 'captureLandingPage', 'label' => 'Landing Page URL', 'desc' => 'First page visited'],
                        ];
                    @endphp
                    <div class="rounded-lg border border-slate-100 bg-slate-50/60 divide-y divide-slate-100">
                        @foreach ($params as $param)
                            <label class="flex cursor-pointer items-center justify-between px-4 py-3">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">{{ $param['label'] }}</p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $param['desc'] }}</p>
                                </div>
                                <input type="checkbox" wire:model="{{ $param['model'] }}" class="h-4 w-4 rounded border-slate-300 text-teal-600 focus:ring-teal-500" />
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end border-t border-slate-100 pt-4">
                    <x-ui.button variant="primary" size="sm" wireClick="saveAdvanced">Save Advanced Settings</x-ui.button>
                </div>
            </div>
        @endif
    </div>

    {{-- Event Diagnostics --}}
    <x-ui.card>
        <x-slot:title>Event Diagnostics</x-slot:title>
        <x-slot:description>Recent tracking events fired to your connected providers.</x-slot:description>
        <x-slot:actions>
            <x-ui.button variant="ghost" size="sm" wireClick="$refresh">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Refresh
            </x-ui.button>
        </x-slot:actions>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-4">
            <div class="relative flex-1">
                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z"/>
                </svg>
                <input
                    type="text"
                    wire:model.live="eventSearch"
                    placeholder="Search events..."
                    class="block w-full rounded-lg border border-slate-200 bg-white py-2 pl-9 pr-3 text-sm placeholder-slate-400 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                />
            </div>
            <select
                wire:model.live="eventFilter"
                class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
            >
                <option value="">All providers</option>
                @foreach ($providers as $p)
                    <option value="{{ $p->value }}">{{ $p->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="overflow-hidden rounded-lg border border-slate-200">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Event</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Provider</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500">Amount</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500 sm:table-cell">Campaign</th>
                        <th class="hidden px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-slate-500 md:table-cell">Timestamp</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($this->events as $event)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-4 py-3 font-medium text-slate-900">{{ $event['event_name'] }}</td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ \App\Enums\TrackingProvider::from($event['provider'])->label() }}
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.status-badge :status="$event['status']" size="sm">{{ ucfirst($event['status']) }}</x-ui.status-badge>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $event['amount'] ? number_format((float) $event['amount'], 2) : '—' }}
                            </td>
                            <td class="hidden px-4 py-3 text-slate-500 sm:table-cell">{{ $event['campaign_name'] ?? '—' }}</td>
                            <td class="hidden px-4 py-3 text-slate-500 md:table-cell">
                                {{ $event['created_at'] ? \Carbon\Carbon::parse($event['created_at'])->diffForHumans() : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-16 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-medium text-slate-900">No events recorded yet</p>
                                <p class="mt-1 text-xs text-slate-500">Events appear here once a provider is configured and donors visit your pages.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    {{-- Donation Attribution Preview --}}
    <x-ui.card>
        <x-slot:title>Donation Attribution Preview</x-slot:title>
        <x-slot:description>How attribution data is stored with each donation.</x-slot:description>
        <x-slot:actions>
            <x-ui.button variant="ghost" size="sm" wireClick="$toggle('showAttribution')">
                {{ $showAttribution ? 'Hide' : 'Show example' }}
            </x-ui.button>
        </x-slot:actions>

        @if ($showAttribution)
            <div class="mt-2 overflow-hidden rounded-xl border border-slate-200 bg-slate-50/40">
                <div class="flex items-center justify-between border-b border-slate-200 bg-white px-5 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-100">
                            <svg class="h-4 w-4 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Donation received</p>
                            <p class="text-xs text-slate-500">June 16, 2026 at 08:33 AM</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xl font-bold tracking-tight text-slate-900">MYR 250.00</p>
                        <x-ui.status-badge status="success" size="sm">Succeeded</x-ui.status-badge>
                    </div>
                </div>

                <div class="divide-y divide-slate-100">
                    @php
                        $attributionRows = [
                            ['label' => 'Donation Amount', 'value' => 'MYR 250.00', 'mono' => false],
                            ['label' => 'Source', 'value' => 'facebook', 'mono' => true],
                            ['label' => 'Medium', 'value' => 'cpc', 'mono' => true],
                            ['label' => 'Campaign', 'value' => 'ramadan-2026-retargeting', 'mono' => true],
                            ['label' => 'Landing Page', 'value' => 'https://ihsan.my/donate/ramadan', 'mono' => true, 'truncate' => true],
                            ['label' => 'Referrer', 'value' => 'https://www.facebook.com/', 'mono' => true, 'truncate' => true],
                            ['label' => 'FBCLID', 'value' => 'IwAR2xKj7Lm9nPqRsTuVwXyZ...', 'mono' => true],
                            ['label' => 'GCLID', 'value' => '—', 'mono' => false],
                            ['label' => 'TTCLID', 'value' => '—', 'mono' => false],
                            ['label' => 'Timestamp', 'value' => '2026-06-16T00:33:14Z', 'mono' => true],
                        ];
                    @endphp
                    @foreach ($attributionRows as $row)
                        <div class="flex items-center gap-4 px-5 py-3">
                            <span class="w-36 shrink-0 text-xs font-medium text-slate-500">{{ $row['label'] }}</span>
                            <span class="text-sm {{ ($row['mono'] ?? false) ? 'font-mono text-slate-700' : 'text-slate-900 font-medium' }} {{ ($row['truncate'] ?? false) ? 'truncate' : '' }}">
                                {{ $row['value'] }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-slate-200 bg-white px-5 py-4">
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-500">Events fired</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($providers as $p)
                            @php
                                $c = collect($this->configurations)->first(fn ($cfg) => $cfg->provider === $p);
                                $fired = $c && $c->isConfigured();
                            @endphp
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $fired ? 'bg-teal-50 text-teal-700 ring-teal-600/20' : 'bg-slate-50 text-slate-500 ring-slate-500/10' }}">
                                <span class="h-1.5 w-1.5 rounded-full {{ $fired ? 'bg-teal-400' : 'bg-slate-300' }}"></span>
                                {{ $p->label() }} · {{ $fired ? 'sent' : 'not configured' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        @else
            <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50/40 py-10 text-center">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-slate-100">
                    <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z"/>
                    </svg>
                </div>
                <p class="mt-3 text-sm font-medium text-slate-900">See how attribution is captured</p>
                <p class="mt-1 text-xs text-slate-500 max-w-sm">View an example of the full attribution data stored with each donation — source, medium, campaign, and all click IDs.</p>
                <x-ui.button variant="outline" size="sm" class="mt-4" wireClick="$toggle('showAttribution')">
                    Show attribution example
                </x-ui.button>
            </div>
        @endif
    </x-ui.card>

</div>
