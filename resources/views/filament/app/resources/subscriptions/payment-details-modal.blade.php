<div class="space-y-6">
    {{-- Installment Amount --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Installment amount</label>
        <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">
                {{ \App\Support\Currency::symbol($record->currency) }}
            </span>
            <input
                type="text"
                value="{{ number_format($record->amount, 2) }}"
                class="w-full rounded-lg border border-gray-300 py-2 pl-8 pr-16 text-sm focus:border-primary-500 focus:ring-primary-500"
                readonly
            >
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">
                {{ strtoupper($record->currency) }}
            </span>
        </div>
    </div>

    {{-- Frequency --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Frequency</label>
        <select class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500" disabled>
            <option>{{ ucfirst($record->interval->value) }}</option>
        </select>
    </div>

    {{-- Starting On --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Starting on</label>
        <select class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500" disabled>
            @php
                $startDate = $record->current_period_start ?? $record->created_at;
            @endphp
            <option>{{ $startDate?->format('jS') }}</option>
        </select>
    </div>

    {{-- End Date --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">End date</label>
        <div class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm text-gray-500">
            <span class="flex items-center gap-2">
                <x-heroicon-o-calendar class="w-4 h-4" />
                Unlimited
            </span>
        </div>
    </div>

    {{-- Max Plan Amount --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Max plan amount</label>
        <button type="button" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
            <x-heroicon-o-plus class="w-4 h-4" />
            Add limit
        </button>
    </div>

    {{-- Max Plan Installments --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Max plan installments</label>
        <button type="button" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
            <x-heroicon-o-plus class="w-4 h-4" />
            Add limit
        </button>
    </div>

    {{-- Transaction Costs --}}
    <div class="border-t pt-4">
        <p class="text-sm text-gray-600">
            Estimated transaction costs: <span class="font-semibold">{{ \App\Support\Currency::symbol($record->currency) }}{{ number_format($record->amount * 0.03 + 0.50, 2) }}</span>
        </p>
        
        <label class="mt-3 flex items-center gap-2 rounded-lg bg-gray-50 px-4 py-3 cursor-pointer">
            <input type="checkbox" checked class="rounded border-gray-300 text-primary-600 focus:ring-primary-500" disabled>
            <span class="text-sm text-gray-700">Cover transaction costs</span>
            <x-heroicon-o-question-mark-circle class="w-4 h-4 text-gray-400" />
        </label>
    </div>

    {{-- Payment Method --}}
    <div class="border-t pt-4">
        <label class="block text-sm font-medium text-gray-700 mb-3">Payment method</label>
        
        <div class="space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" name="payment_method" value="current" checked class="text-primary-600 focus:ring-primary-500">
                <div class="flex items-center gap-2">
                    <span class="text-blue-600">
                        <x-heroicon-o-credit-card class="w-6 h-6" />
                    </span>
                    <span class="text-sm text-gray-600">VISA <span class="text-gray-400">•• 3397 • Exp. 07/26</span></span>
                </div>
            </label>
            
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="radio" name="payment_method" value="new" class="text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-gray-600">New credit card</span>
            </label>
        </div>
    </div>

    {{-- Info Box --}}
    <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">
        The next installment of {{ \App\Support\Currency::symbol($record->currency) }}{{ number_format($record->amount + ($record->amount * 0.03 + 0.50), 2) }} (with costs covered) will run on {{ $record->current_period_end?->format('M j, Y, g:i A') ?? 'N/A' }}.
    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-3 pt-2">
        <button type="button" x-on:click="$dispatch('close-modal')" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
            Cancel
        </button>
        <button type="button" class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">
            Save changes
        </button>
    </div>
</div>
