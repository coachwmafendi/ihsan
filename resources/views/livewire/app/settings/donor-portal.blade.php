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
                    <li class="font-medium text-slate-900">Donor Portal</li>
                </ol>
            </nav>
        </x-slot:subtitle>
    </x-ui.page-header>



    @php
        $org = Auth::user()?->organization;
        $portalUrl = $org?->code ? route('donorportal.dashboard', ['organization' => $org->code]) : null;
    @endphp

    <form wire:submit="save" class="space-y-6">
        <x-ui.card title="Donor Portal" description="Your public-facing portal where donors can log in and view their donation history.">
            @if ($portalUrl)
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Portal URL</label>
                        <div class="mt-1 flex items-center gap-3">
                            <input type="text" readonly value="{{ $portalUrl }}" class="block w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 shadow-sm">
                            <a href="{{ $portalUrl }}" target="_blank" rel="noopener noreferrer"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Open
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-sm text-slate-500">No portal URL available. Contact support if this persists.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Portal Settings" description="Customise your public donation portal.">
            <div class="grid gap-6 md:grid-cols-2">
                <div>
                    <label for="portal_tagline" class="block text-sm font-medium text-slate-700">Portal Tagline</label>
                    <input type="text" id="portal_tagline" wire:model="portal_tagline" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    @error('portal_tagline') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="portal_reply_to_email" class="block text-sm font-medium text-slate-700">Reply-To Email</label>
                    <input type="email" id="portal_reply_to_email" wire:model="portal_reply_to_email" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500">
                    @error('portal_reply_to_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="portal_receipt_footer" class="block text-sm font-medium text-slate-700">Receipt Footer Text</label>
                    <textarea id="portal_receipt_footer" wire:model="portal_receipt_footer" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"></textarea>
                    @error('portal_receipt_footer') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
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
