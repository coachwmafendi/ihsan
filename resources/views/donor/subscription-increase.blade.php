@extends('donor.layout')

@section('title', 'Increase Donation')

@section('content')
<div x-data="{
    selected: null,
    customAmount: '',
    currentAmount: {{ $currentAmount }},
    symbol: @js($symbol),
    currency: @js($currency),
    interval: @js($interval),
    processing: false,
    success: false,
    error: null,
    newAmount: null,
    get newTotal() {
        if (this.selected === 'custom') {
            const amt = parseFloat(this.customAmount) || 0;
            return this.currentAmount + amt;
        }
        return this.currentAmount + (this.selected || 0);
    },
    get increment() {
        if (this.selected === 'custom') {
            return parseFloat(this.customAmount) || 0;
        }
        return this.selected || 0;
    },
    async submitIncrease() {
        if (this.processing) return;

        const total = this.newTotal;
        if (total <= this.currentAmount) return;

        this.processing = true;
        this.error = null;

        try {
            const res = await fetch(
                @js($changeAmountUrl ?? route('donorportal.subscriptions.change-amount', ['organization' => $organization, 'subscription' => $subscription])),
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ new_amount: total }),
                }
            );

            if (!res.ok) {
                const data = await res.json().catch(() => ({}));
                this.error = data.error || 'Unable to update subscription amount. Please try again later.';
                this.processing = false;
                return;
            }

            const data = await res.json();
            this.newAmount = data.new_amount;
            this.success = true;
            this.processing = false;
        } catch (e) {
            this.error = 'Unable to update subscription amount. Please try again later.';
            this.processing = false;
        }
    }
}"
     x-init="selected = {{ $selectedIncrement }}"
     class="max-w-3xl mx-auto relative">

    {{-- Full-screen processing overlay --}}
    <div x-show="processing"
         x-cloak
         class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm"
         x-transition.opacity.duration.200ms>
        <div class="flex flex-col items-center gap-4">
            <svg class="h-10 w-10 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <p class="text-sm font-semibold text-slate-700">Updating your subscription...</p>
        </div>
    </div>

    {{-- Success state --}}
    <div x-show="success" x-cloak x-transition.opacity.duration.300ms class="text-center py-12">
        <div class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100">
            <svg class="h-8 w-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <h2 class="text-2xl font-black text-slate-900 [letter-spacing:-0.02em]">Subscription Updated!</h2>
        <p class="mt-3 text-base text-slate-600">
            Your donation has been increased to<br>
            <span class="font-bold text-slate-900"
                  x-text="symbol + ' ' + (newAmount ?? newTotal).toFixed(2) + ' ' + currency.toUpperCase() + '/' + interval"></span>
        </p>
        <a href="{{ route('donorportal.subscriptions', $organization) }}"
           class="mt-8 inline-flex items-center justify-center rounded-lg bg-blue-600 px-8 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-500">
            Close
        </a>
    </div>

    {{-- Form state --}}
    <div x-show="!success" x-transition.opacity.duration.200ms>
        <div class="mb-8">
            <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">
                Boost your impact by increasing your donations
            </h1>
            <p class="mt-2 text-base text-slate-700">
                Increase your current <span class="font-semibold">{{ $symbol }} {{ number_format($currentAmount, 2) }} {{ strtoupper($currency) }}/{{ $interval }}</span> donation by:
            </p>
        </div>

        {{-- Error banner --}}
        <div x-show="error" x-cloak
             class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700"
             x-text="error">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-8">
            @foreach ($presetOptions as $option)
                <button
                    type="button"
                    @click="selected = {{ $option['increment'] }}; error = null"
                    class="relative text-left rounded-xl border-2 p-4 transition"
                    :class="selected === {{ $option['increment'] }}
                        ? 'border-blue-500 bg-blue-50/50 shadow-sm'
                        : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
                >
                    <p class="text-lg font-bold text-slate-900">{{ $option['label'] }}</p>
                    <p class="mt-1.5 text-sm text-slate-600">
                        Future donations will be<br>
                        <span class="font-semibold">{{ $symbol }} {{ number_format($currentAmount + $option['increment'], 2) }} {{ strtoupper($currency) }}/{{ $interval }}</span>
                    </p>
                </button>
            @endforeach

            {{-- Custom amount card --}}
            <button
                type="button"
                @click="selected = 'custom'; error = null; $nextTick(() => $refs.customInput?.focus())"
                class="relative text-left rounded-xl border-2 p-4 transition"
                :class="selected === 'custom'
                    ? 'border-blue-500 bg-blue-50/50 shadow-sm'
                    : 'border-slate-200 bg-white hover:border-slate-300 hover:bg-slate-50'"
            >
                <p class="text-lg font-bold" :class="selected === 'custom' ? 'text-slate-900' : 'text-slate-500'">Other amount</p>
                <p class="mt-1.5 text-sm text-slate-500">Choose a custom<br>increase amount</p>
            </button>
        </div>

        {{-- Custom amount input --}}
        <div x-show="selected === 'custom'" x-transition.opacity.duration.200ms class="mb-8">
            <label class="block text-base font-bold text-slate-900 mb-3">Custom increase amount</label>
            <div class="relative max-w-sm">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-base font-bold text-slate-400">{{ $symbol }}</span>
                <input
                    type="number"
                    step="0.01"
                    min="1"
                    max="99999.99"
                    x-ref="customInput"
                    x-model="customAmount"
                    @input="customAmount = parseFloat($event.target.value) > 99999.99 ? '99999.99' : $event.target.value"
                    placeholder="0.00"
                    class="block w-full appearance-none rounded-xl border-2 border-slate-900 bg-white py-3 pl-11 pr-4 text-base font-bold text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-blue-600 focus:outline-none focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                >
            </div>
            <p class="mt-3 text-sm text-slate-600">
                Future donations will be
                <span class="font-bold text-slate-900" x-text="symbol + ' ' + newTotal.toFixed(2) + ' ' + currency.toUpperCase() + '/' + interval"></span>
            </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-3 max-w-lg">
            <button
                type="button"
                @click="submitIncrease()"
                :disabled="processing || (selected === 'custom' && (!customAmount || parseFloat(customAmount) <= 0 || newTotal > 99999.99))"
                class="flex-1 rounded-lg bg-[#228B22] px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-[#1a6b1a] disabled:opacity-50 disabled:cursor-not-allowed"
            >
                Confirm
            </button>

            <a
                href="{{ route('donorportal.subscriptions', $organization) }}"
                class="flex-1 inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
            >
                No, thanks
            </a>
        </div>
    </div>
</div>
@endsection
