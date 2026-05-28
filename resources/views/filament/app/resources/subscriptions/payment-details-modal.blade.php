@php
    $currentBillingDay = (int) ($record->current_period_start ?? $record->created_at)?->format('j');
    $currentCancelAt = $record->cancel_at
        ? $record->cancel_at->format('Y-m-d')
        : ($record->current_period_start ?? $record->created_at)?->addYears(2)->format('Y-m-d') ?? '';
    $stripeAccount = $record->campaign->organization->stripe_account_id ?? null;
    $processingFeePercent = (float) config('services.stripe.processing_fee_percent', 2.5) / 100;
    $feeAmount = $record->amount * (0.03 + $processingFeePercent) + 0.50;
    $displayTotal = $record->amount + $feeAmount;
    $initialData = [
        'interval' => $record->interval->value,
        'billingDay' => $currentBillingDay,
        'cancelAt' => $currentCancelAt,
        'unlimitedEndDate' => $currentCancelAt === '',
        'coverFee' => (bool) $record->cover_fee,
        'amount' => (float) $record->amount,
        'processingFeeRate' => $processingFeePercent,
    ];
@endphp

<div
    class="space-y-6"
    x-data="paymentDetailsModal({{ json_encode($clientSecret) }}, {{ json_encode($stripeAccount) }}, {{ json_encode($initialData) }})"
    x-init="init()"
    @submit-payment-details.window="save()"
    data-stripe-key="{{ config('services.stripe.key') }}"
>
    <style>[x-cloak] { display: none !important; }</style>

    {{-- Installment Amount --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Installment amount</label>
        <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ \App\Support\Currency::symbol($record->currency) }}</span>
            <input type="number" min="0.01" step="0.01" x-model.number="amount" class="w-full rounded-lg border border-gray-300 py-2 pl-11 pr-16 text-sm bg-white text-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">{{ strtoupper($record->currency) }}</span>
        </div>
    </div>

    {{-- Frequency --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Frequency</label>
        <select x-model="interval" class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm bg-white text-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <option value="weekly">Weekly</option>
            <option value="monthly">Monthly</option>
            <option value="yearly">Yearly</option>
        </select>
    </div>

    {{-- Starting On --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Starting on</label>
        <div class="flex-1 flex items-center gap-2">
            <input type="number" min="1" max="28" x-model.number="billingDay" class="w-24 rounded-lg border border-gray-300 py-2 px-3 text-sm bg-white text-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500">
            <span class="text-sm text-gray-500">day of month</span>
        </div>
    </div>

    {{-- End Date --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">End date</label>
        <div class="flex-1 flex items-center gap-3">
            <input type="date" x-model="cancelAt" :disabled="unlimitedEndDate" x-show="!unlimitedEndDate" class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm bg-white text-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 disabled:bg-gray-50 disabled:text-gray-400">
            <span x-show="unlimitedEndDate" class="flex-1 text-sm text-gray-400 italic">No end date</span>
            <label class="flex items-center gap-2 cursor-pointer whitespace-nowrap">
                <input type="checkbox" x-model="unlimitedEndDate" @change="if (unlimitedEndDate) cancelAt = ''" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-gray-600">Unlimited</span>
            </label>
        </div>
    </div>

    {{-- Transaction Costs --}}
    <div class="border-t pt-4">
        <p class="text-sm text-gray-600">
            Estimated transaction fees (Stripe + processing): <span class="font-semibold ml-1" x-text="'{{ \App\Support\Currency::symbol($record->currency) }} ' + (amount * (0.03 + processingFeeRate) + 0.50).toFixed(2)"></span>
        </p>

        <label class="mt-3 flex items-center gap-3 rounded-lg bg-gray-50 px-4 py-3 cursor-pointer">
            <input type="checkbox" x-model="coverFee" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
            <span class="text-sm text-gray-700">Cover transaction costs</span>
            <x-heroicon-o-question-mark-circle class="w-4 h-4 text-gray-400" />
        </label>
    </div>

    {{-- Payment Method --}}
    <div class="border-t pt-4">
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
            <div x-show="success" class="rounded-lg bg-green-50 p-3 text-sm text-green-700">Changes saved successfully.</div>
        </div>
    </div>

    {{-- Info Box --}}
    @php $currencySymbol = \App\Support\Currency::symbol($record->currency); @endphp
    <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">
        The next installment of
        <span class="font-semibold" x-text="'{{ $currencySymbol }} ' + (coverFee ? (amount * (1.03 + processingFeeRate) + 0.50).toFixed(2) : parseFloat(amount).toFixed(2))"></span>
        <span x-show="coverFee">(with costs covered)</span>
        will run on {{ $record->current_period_end?->format('M j, Y, g:i A') ?? 'N/A' }},
        <span x-show="!unlimitedEndDate">ending on <span class="font-semibold" x-text="cancelAt ? new Date(cancelAt).toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'"></span>.</span>
        <span x-show="unlimitedEndDate">with no end date.</span>
    </div>

    {{-- Success message (outside card section) --}}
    <div x-show="success && paymentMethod === 'current'" class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
        Changes saved successfully.
    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-3 pt-2">
        <button type="button" @click="$wire.unmountAction()" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Cancel
        </button>
        <button
            type="button"
            @click="$dispatch('submit-payment-details')"
            :disabled="loading"
            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
        >
            <span x-show="!loading">Save changes</span>
            <span x-show="loading">Saving...</span>
        </button>
    </div>
</div>
