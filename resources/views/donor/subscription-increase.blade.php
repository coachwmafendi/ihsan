@extends('donor.layout')

@section('title', 'Increase Donation')

@section('content')
<div x-data="{
    selected: null,
    customAmount: '',
    currentAmount: {{ $currentAmount }},
    symbol: @js($symbol),
    interval: @js($interval),
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
    formatAmount(amount) {
        return this.symbol + ' ' + amount.toFixed(2) + ' ' + this.currency.toUpperCase() + '/' + this.interval;
    },
    confirmLoading: false,
}"
     x-init="selected = 5"
     class="max-w-3xl mx-auto">

    <div class="mb-8">
        <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">
            Boost your impact by increasing your donations
        </h1>
        <p class="mt-2 text-base text-slate-700">
            Increase your current <span class="font-semibold">{{ $symbol }} {{ number_format($currentAmount, 2) }} {{ strtoupper($currency) }}/{{ $interval }}</span> donation by:
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-8">
        @foreach ($presetOptions as $option)
            <button
                type="button"
                @click="selected = {{ $option['increment'] }}"
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
            @click="selected = 'custom'; $nextTick(() => $refs.customInput?.focus())"
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
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-base font-bold text-slate-900">{{ $symbol }}</span>
            <input
                type="number"
                step="0.01"
                min="1"
                x-ref="customInput"
                x-model="customAmount"
                placeholder="0.00"
                class="block w-full appearance-none rounded-xl border-2 border-slate-900 bg-white py-3 pl-11 pr-4 text-base font-bold text-slate-900 placeholder-slate-400 shadow-sm transition focus:border-blue-600 focus:outline-none focus:ring-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
            >
        </div>
        <p class="mt-3 text-sm text-slate-600">
            Future donations will be
            <span class="font-bold text-slate-900" x-text="symbol + ' ' + newTotal.toFixed(2) + ' ' + '{{ strtoupper($currency) }}'.toUpperCase() + '/' + interval"></span>
        </p>
    </div>

    @if ($subscription->cover_fee)
        <p class="mb-8 text-sm text-slate-700">
            Thank you for continuing to cover transaction costs for your donations <span class="text-red-500">❤️</span>
        </p>
    @endif

    <div class="flex flex-col sm:flex-row gap-3 max-w-lg">
        <form
            method="POST"
            action="{{ route('donorportal.subscriptions.change-amount', ['organization' => $organization, 'subscription' => $subscription]) }}"
            class="flex-1"
            @submit.prevent="
                confirmLoading = true;
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'new_amount';
                input.value = newTotal;
                $el.appendChild(input);
                $el.submit();
            "
        >
            @csrf
            <button
                type="submit"
                :disabled="confirmLoading || (selected === 'custom' && (!customAmount || parseFloat(customAmount) <= 0))"
                class="w-full rounded-lg bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                x-text="confirmLoading ? 'Confirming...' : 'Confirm'"
            >
                Confirm
            </button>
        </form>

        <a
            href="{{ route('donorportal.subscriptions', $organization) }}"
            class="flex-1 inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 hover:text-slate-900"
        >
            No, thanks
        </a>
    </div>
</div>
@endsection
