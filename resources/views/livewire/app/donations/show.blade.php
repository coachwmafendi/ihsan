{{-- resources/views/livewire/app/donations/show.blade.php --}}
<div class="space-y-6" x-data="{ copied: false }">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Donation {{ $donation->public_id }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $donation->created_at->format('M d, Y H:i') }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if ($this->canRefund())
                <x-ui.button wireClick="refund" variant="danger" onclick="return confirm('Are you sure you want to refund this donation? This action cannot be undone.')">Refund</x-ui.button>
            @endif
            <x-ui.badge status="{{ $donation->status->value }}" size="md">
                {{ ucfirst($donation->status->value === 'succeeded' ? 'Succeed' : $donation->status->value) }}
            </x-ui.badge>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Donation Details --}}
            <x-ui.card title="Donation Details">
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Gross Amount</dt>
                        <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $donation->formatted_amount }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Net Amount</dt>
                        <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $this->netAmount }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Payment Method</dt>
                        <dd class="mt-1 text-sm text-slate-900">{{ $donation->payment_method_display }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Stripe Payment Intent</dt>
                        <dd class="mt-1 flex items-center gap-2">
                            <code class="rounded bg-slate-50 px-2 py-0.5 text-xs text-slate-600 font-mono">{{ $donation->stripe_payment_intent_id ?? '—' }}</code>
                            @if ($donation->stripe_payment_intent_id)
                                <button
                                    x-on:click="navigator.clipboard.writeText('{{ $donation->stripe_payment_intent_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                    title="Copy"
                                >
                                    <x-heroicon-o-clipboard-document class="size-4" />
                                </button>
                                <span x-show="copied" x-transition class="text-xs text-emerald-600">Copied!</span>
                            @endif
                        </dd>
                    </div>
                </div>
            </x-ui.card>

            {{-- Fees Breakdown --}}
            <x-ui.card title="Fees Breakdown">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Gross Amount</dt>
                        <dd class="text-sm font-medium text-slate-900">{{ $donation->formatted_amount }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Stripe Fee</dt>
                        <dd class="text-sm font-medium text-slate-900">- {{ $donation->currency_symbol }} {{ number_format((float) $donation->stripe_fee, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Processing Fee</dt>
                        <dd class="text-sm font-medium text-slate-900">- {{ $donation->currency_symbol }} {{ number_format((float) $donation->processing_fee, 2) }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-slate-500">Donor Fee Covered</dt>
                        <dd class="text-sm font-medium text-slate-900">+ {{ $donation->currency_symbol }} {{ number_format((float) $donation->donor_fee_covered, 2) }}</dd>
                    </div>
                    <div class="border-t border-slate-100 pt-3 flex justify-between">
                        <dt class="text-sm font-semibold text-slate-900">Net Amount</dt>
                        <dd class="text-sm font-bold text-slate-900">{{ $this->netAmount }}</dd>
                    </div>
                </dl>
            </x-ui.card>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Donor --}}
            <x-ui.card title="Donor">
                @if ($donation->donor)
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-50">
                            <x-heroicon-o-user class="size-5 text-slate-400" />
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $donation->donor->name }}</p>
                            <p class="text-xs text-slate-500">{{ $donation->donor->email }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="/app/donors/{{ $donation->donor->public_id }}" wire:navigate class="text-sm font-medium text-teal-600 hover:text-teal-700">
                            View donor profile →
                        </a>
                    </div>
                @else
                    <p class="text-sm text-slate-500">Anonymous donation</p>
                @endif
            </x-ui.card>

            {{-- Campaign --}}
            <x-ui.card title="Campaign">
                @if ($donation->campaign)
                    <div class="flex items-center gap-3">
                        @if ($donation->campaign->image_path && Storage::disk('public')->exists($donation->campaign->image_path))
                            <img src="{{ Storage::disk('public')->url($donation->campaign->image_path) }}" alt="" class="h-10 w-10 rounded-lg object-cover" />
                        @else
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-teal-50">
                                <x-heroicon-o-megaphone class="size-5 text-teal-600" />
                            </div>
                        @endif
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $donation->campaign->title }}</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('app.campaigns.edit', $donation->campaign) }}" wire:navigate class="text-sm font-medium text-teal-600 hover:text-teal-700">
                            View campaign →
                        </a>
                    </div>
                @else
                    <p class="text-sm text-slate-500">No campaign linked</p>
                @endif
            </x-ui.card>
        </div>
    </div>

    {{-- Back Link --}}
    <div>
        <a href="{{ route('app.donations.index') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-teal-600 hover:text-teal-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back to donations
        </a>
    </div>
</div>
