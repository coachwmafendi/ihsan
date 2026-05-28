<div class="space-y-6">
    <style>[x-cloak] { display: none !important; }</style>

    {{-- Installment Amount --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Installment amount</label>
        <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ \App\Support\Currency::symbol($record->currency) }}</span>
            <input type="text" value="{{ number_format($record->amount, 2) }}" readonly class="w-full rounded-lg border border-gray-300 py-2 pl-11 pr-16 text-sm bg-gray-50">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">{{ strtoupper($record->currency) }}</span>
        </div>
    </div>

    {{-- Frequency --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Frequency</label>
        <div class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm bg-gray-50 text-gray-700">
            {{ ucfirst($record->interval->value) }}
        </div>
    </div>

    {{-- Starting On --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Starting on</label>
        <div class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm bg-gray-50 text-gray-700">
            {{ ($record->current_period_start ?? $record->created_at)?->format('jS') ?? '19th' }}
        </div>
    </div>

    {{-- End Date --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">End date</label>
        <div class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm bg-gray-50 text-gray-700 flex items-center gap-2">
            <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
            {{ $record->created_at->copy()->addYear()->format('d M Y') }}
        </div>
    </div>

    {{-- Transaction Costs --}}
    <div class="border-t pt-4">
        @php
            $feeAmount = $record->amount * 0.03 + 0.50;
            $totalWithFee = $record->amount + $feeAmount;
        @endphp
        <p class="text-sm text-gray-600">
            Estimated transaction costs: <span class="font-semibold ml-1">{{ \App\Support\Currency::symbol($record->currency) }} {{ number_format($feeAmount, 2) }}</span>
        </p>

        <div class="mt-3 flex items-center gap-2 rounded-lg bg-gray-50 px-4 py-3">
            <svg class="w-4 h-4 text-primary-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            <span class="text-sm text-gray-700">Cover transaction costs</span>
            <x-heroicon-o-question-mark-circle class="w-4 h-4 text-gray-400" />
        </div>
    </div>

    {{-- Payment Method --}}
    @php $stripeAccount = $record->campaign->organization->stripe_account_id ?? null; @endphp
    <div class="border-t pt-4" x-data="paymentDetailsModal({{ json_encode($clientSecret) }}, {{ json_encode($stripeAccount) }})" x-init="init()" @submit-payment-details.window="save()" data-stripe-key="{{ config('services.stripe.key') }}">
        <label class="block text-sm font-medium text-gray-700 mb-3">Payment method</label>

        <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" value="current" x-model="paymentMethod" class="text-primary-600 focus:ring-primary-500">
                <div class="flex items-center gap-2">
                    <span class="text-blue-600"><x-heroicon-o-credit-card class="w-6 h-6" /></span>
                    <span class="text-sm text-gray-600">Current card on file</span>
                </div>
            </label>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" value="new" x-model="paymentMethod" class="text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-gray-600">New credit card</span>
            </label>
        </div>

        <div x-show="paymentMethod === 'new'" x-cloak class="mt-4 space-y-4 rounded-xl bg-gray-50 p-5">
            <div x-show="!clientSecret" class="text-sm text-red-600">
                Unable to load card form. Please refresh and try again.
            </div>
            <div x-show="clientSecret">
                <label class="block text-sm font-semibold text-gray-900 mb-2">Card details</label>
                <div id="stripe-card-element" wire:ignore class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 min-h-[44px]"></div>
                <div class="mt-2 text-sm text-red-600" x-text="cardError"></div>
            </div>

            <div x-show="loading" class="text-sm text-gray-500">Processing...</div>
            <div x-show="success" class="rounded-lg bg-green-50 p-3 text-sm text-green-700">Payment method saved successfully.</div>
        </div>
    </div>

    {{-- Info Box --}}
    @php
        $displayFee = $record->amount * 0.03 + 0.50;
        $displayTotal = $record->amount + $displayFee;
    @endphp
    <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">
        The next installment of <span class="font-semibold">{{ \App\Support\Currency::symbol($record->currency) }} {{ number_format($displayTotal, 2) }}</span>
        (with costs covered) will run on {{ $record->current_period_end?->format('M j, Y, g:i A') ?? 'N/A' }}.
    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-3 pt-2" x-data>
        <button type="button" @click="$wire.unmountAction()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Cancel
        </button>
        <button type="button" @click="$dispatch('submit-payment-details')" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
            Save changes
        </button>
    </div>
</div>
