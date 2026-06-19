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



    <form wire:submit="save" class="space-y-6" x-data="{ newDomain: '' }">
        <x-ui.card title="Allowed Domains" description="Domains permitted to embed your elements and checkout modal. Only requests originating from these domains will be accepted.">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <p class="text-sm text-slate-600">
                        <span class="font-medium text-slate-900">{{ count($allowed_domains) }}</span> of <span class="font-medium text-slate-900">{{ App\Livewire\App\Settings\AllowDomains::MAX_ALLOWED_DOMAINS }}</span> domains added
                    </p>
                </div>

                @if (count($allowed_domains) > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach ($allowed_domains as $i => $domain)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-teal-50 border border-teal-200 px-3 py-1 text-sm font-medium text-teal-700">
                                {{ $domain }}
                                <button type="button" wire:click="removeDomain({{ $i }})" class="text-teal-400 hover:text-red-500 transition-colors leading-none">&times;</button>
                            </span>
                        @endforeach
                    </div>
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

                <p class="text-xs text-slate-400">Enter domain only (e.g. <code>mywebsite.com</code>) or full URL — <code>www.</code> and path are stripped automatically on save.</p>
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
