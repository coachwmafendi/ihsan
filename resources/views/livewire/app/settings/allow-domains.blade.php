<div class="space-y-6">
    <x-ui.page-header title="Settings">
        <x-slot:subtitle>
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 text-sm text-slate-500">
                    <li>Settings</li>
                    <li>
                        <svg class="mx-1 h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </li>
                    <li class="font-medium text-slate-900">Allowed Domains</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>



    <form wire:submit="save" class="space-y-6" x-data="{ newDomain: '' }" wire:init="loadDomainStatuses">
        <x-ui.card title="Allowed Domains" description="Domains permitted to embed your elements and checkout modal. Only requests originating from these domains will be accepted.">
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm text-slate-600">
                        <span class="font-medium text-slate-900">{{ count($allowed_domains) }}</span> of <span class="font-medium text-slate-900">{{ App\Livewire\App\Settings\AllowDomains::MAX_ALLOWED_DOMAINS }}</span> domains added
                    </p>

                    @if (count($allowed_domains) > 0)
                        <button
                            type="button"
                            wire:click="recheckDomains"
                            wire:loading.attr="disabled"
                            wire:target="recheckDomains"
                            class="shrink-0 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:text-slate-400">
                            <span wire:loading.remove wire:target="recheckDomains">Recheck wallets</span>
                            <span wire:loading wire:target="recheckDomains">Rechecking...</span>
                        </button>
                    @endif
                </div>

                @if (count($allowed_domains) > 0)
                    @php
                        $statusTones = [
                            'active' => 'bg-emerald-50 border-emerald-200 text-emerald-700',
                            'partial' => 'bg-amber-50 border-amber-200 text-amber-700',
                            'pending' => 'bg-slate-100 border-slate-200 text-slate-600',
                            'failed' => 'bg-red-50 border-red-200 text-red-700',
                        ];
                    @endphp

                    <ul class="divide-y divide-slate-100 rounded-lg border border-slate-200">
                        @forelse ($this->checkoutDomains() as $checkoutDomain)
                            @php $checkoutStatus = $this->walletStatusFor($checkoutDomain); @endphp
                            <li class="flex flex-wrap items-center justify-between gap-2 bg-slate-50 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $checkoutDomain }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">Ihsan checkout — a page your donation form runs on. Always allowed, and not counted against your limit.</p>

                                    @if ($checkoutStatus['error'])
                                        <p class="mt-0.5 text-xs text-red-600">{{ $checkoutStatus['error'] }}</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    @if (! $statuses_loaded)
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">Checking...</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusTones[$checkoutStatus['tone']] }}">{{ $checkoutStatus['label'] }}</span>
                                    @endif
                                </div>
                            </li>
                        @empty
                            <li class="bg-slate-50 px-3 py-2.5">
                                <p class="text-xs text-red-600">Ihsan has no checkout domain configured, so nothing was registered with Stripe and wallets cannot appear. Contact support.</p>
                            </li>
                        @endforelse

                        @foreach ($allowed_domains as $i => $domain)
                            @php $status = $this->walletStatusFor($domain); @endphp

                            <li class="flex flex-wrap items-center justify-between gap-2 px-3 py-2.5">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-slate-900">{{ $domain }}</p>

                                    @if ($status['error'])
                                        <p class="mt-0.5 text-xs text-red-600">{{ $status['error'] }}</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 items-center gap-2">
                                    @if (! $statuses_loaded)
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-500">Checking...</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium {{ $statusTones[$status['tone']] }}">{{ $status['label'] }}</span>
                                    @endif

                                    <button type="button" wire:click="removeDomain({{ $i }})" class="text-lg leading-none text-slate-400 transition-colors hover:text-red-500" aria-label="Remove {{ $domain }}">&times;</button>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    @php $unregistered = $this->unregisteredEmbeddingDomains; @endphp

                    @if ($unregistered !== [])
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2.5">
                            <p class="text-sm font-medium text-amber-900">Donations are coming from domains you have not added</p>
                            <p class="mt-1 text-xs text-amber-800">
                                Stripe treats every subdomain as its own domain, so wallets stay hidden on these pages even when the domain above them is verified. Add each one below.
                            </p>

                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($unregistered as $host)
                                    <button
                                        type="button"
                                        wire:click="addDomain('{{ $host }}')"
                                        class="inline-flex items-center gap-1.5 rounded-full border border-amber-300 bg-white px-3 py-1 text-xs font-medium text-amber-900 transition-colors hover:bg-amber-100">
                                        + {{ $host }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <p class="text-xs text-slate-400">
                        Apple Pay and Google Pay only appear when Stripe has verified both your site and the Ihsan checkout it embeds. Verification runs after you save and can take a moment; use <span class="font-medium text-slate-500">Recheck wallets</span> if a domain stays unverified.
                    </p>
                @else
                    <p class="text-sm text-slate-500">No domains added yet. Add your website domain below.</p>
                @endif

                <div class="flex gap-2">
                    <input
                        type="text"
                        x-model="newDomain"
                        placeholder="e.g. mywebsite.com or https://mywebsite.com"
                        @keydown.enter.prevent="if (newDomain.trim() && {{ count($allowed_domains) }} < {{ App\Livewire\App\Settings\AllowDomains::MAX_ALLOWED_DOMAINS }}) { $wire.addDomain(newDomain); newDomain = ''; }"
                        :disabled="{{ count($allowed_domains) }} >= {{ App\Livewire\App\Settings\AllowDomains::MAX_ALLOWED_DOMAINS }}"
                        class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500"
                    >
                    <button
                        type="button"
                        @click="if (newDomain.trim()) { $wire.addDomain(newDomain); newDomain = ''; }"
                        :disabled="{{ count($allowed_domains) }} >= {{ App\Livewire\App\Settings\AllowDomains::MAX_ALLOWED_DOMAINS }}"
                        class="shrink-0 rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-700 transition-colors disabled:cursor-not-allowed disabled:bg-slate-300">
                        Add
                    </button>
                </div>

                <p class="text-xs text-slate-400">Enter domain only (e.g. <code>mywebsite.com</code>) or full URL — <code>www.</code> and path are stripped automatically, and a domain already on the list cannot be added twice.</p>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end">
            <x-ui.button type="submit" variant="primary" size="lg">
                <span wire:loading.remove wire:target="save">Save Changes</span>
                <span wire:loading wire:target="save">Saving...</span>
            </x-ui.button>
        </div>
    </form>
</div>
