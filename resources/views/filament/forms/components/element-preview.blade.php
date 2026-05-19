@php
    $type = $type ?? null;
    $type = $type instanceof \BackedEnum ? $type->value : $type;
    $config = $config ?? [];
    $defaultAmountsOneTime = collect($config['suggested_amounts_one_time'] ?? $config['suggested_amounts'] ?? [200, 100, 50, 30, 10, 5])
        ->map(fn ($amount) => (int) $amount)
        ->filter(fn (int $amount) => $amount > 0)
        ->values()
        ->all();
    $defaultAmountsMonthly = collect($config['suggested_amounts_monthly'] ?? $config['suggested_amounts'] ?? [100, 50, 30, 20, 10, 5])
        ->map(fn ($amount) => (int) $amount)
        ->filter(fn (int $amount) => $amount > 0)
        ->values()
        ->all();
    $title = $config['title'] ?? $config['heading'] ?? 'Your most generous donation';
    $textColor = $config['text_color'] ?? '#212830';
    $backgroundColor = $config['background_color'] ?? '#FFFFFF';
    $iconColor = $config['icon_color'] ?? '#FF435A';
    $borderSize = $config['border_size'] ?? 2;
    $borderRadius = $config['border_radius'] ?? 6;
    $borderColor = $config['border_color'] ?? '#DEDFF3';
    $showShadow = $config['show_shadow'] ?? false;
    $defaultAmount = (int) ($config['default_amount'] ?? ($defaultAmountsOneTime[0] ?? 5));
    $defaultFrequency = $config['default_frequency'] ?? 'monthly';
    $allowMonthly = $config['allow_monthly'] ?? true;
    $showSuggested = $config['show_suggested'] ?? true;
    $showAmountInput = $config['show_amount_input'] ?? true;
    $showDedication = $config['show_dedication'] ?? true;
    $showComment = $config['show_comment'] ?? true;
    $previewFrequency = $allowMonthly ? $defaultFrequency : 'one_time';
@endphp

<div
    wire:key="element-preview-{{ md5(json_encode([$type, $config])) }}"
    class="rounded-xl border border-zinc-200 bg-white p-6"
>
    <div class="mb-4 flex items-center justify-between">
        <h3 class="text-xs font-semibold uppercase tracking-wider text-zinc-400">Live Preview</h3>
        @if($type)
            <span class="rounded-full bg-zinc-100 px-3 py-1 text-xs font-medium text-zinc-600">
                {{ ucfirst($type) }}
            </span>
        @endif
    </div>

    <div class="flex min-h-[720px] items-center justify-center rounded-xl bg-zinc-50 px-6 py-8">
        @if(! $type)
            <p class="text-sm text-zinc-400">Select a type to preview</p>
        @elseif($type === 'button')
            <button
                type="button"
                class="{{ $config['button_size'] ?? 'text-base px-6 py-3' }} {{ $config['button_color'] ?? 'bg-blue-600 hover:bg-blue-700' }} inline-flex items-center justify-center font-semibold text-white shadow-sm transition-all duration-150 focus:outline-none"
                style="border-radius: {{ ($config['corner_radius'] ?? 8) }}px"
            >
                {{ $config['button_text'] ?? 'Donate Now' }}
            </button>
        @elseif($type === 'form')
            <div
                x-data="{
                    selectedAmount: @js($defaultAmount),
                    frequency: @js($previewFrequency),
                    submitted: false,
                    monthlyAmounts: @js($defaultAmountsMonthly),
                    oneTimeAmounts: @js($defaultAmountsOneTime),
                    get amounts() {
                        return this.frequency === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts;
                    },
                }"
                class="relative mx-auto w-full max-w-[380px]"
            >
                {{-- Phone frame --}}
                <div data-preview-phone class="min-h-[690px] rounded-[42px] bg-zinc-900 p-2.5 shadow-xl shadow-zinc-800/20 ring-1 ring-zinc-800">
                    {{-- Screen --}}
                    <div class="relative min-h-[610px] overflow-hidden rounded-[30px] bg-white shadow-inner">
                        {{-- Dynamic Island (inside screen) --}}
                        <div class="absolute left-1/2 top-3 z-10 flex h-7 w-24 -translate-x-1/2 items-center justify-center rounded-full bg-zinc-950">
                            <div class="size-2.5 rounded-full bg-zinc-900 ring-2 ring-zinc-800"></div>
                        </div>
                        {{-- Submitted state --}}
                        <div x-show="submitted" x-cloak class="flex min-h-[610px] flex-col items-center px-6 pb-20 pt-24 text-center">
                            <div class="mb-6 flex size-16 items-center justify-center rounded-full bg-emerald-50">
                                <svg class="size-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-zinc-900">Thank You!</h3>
                            <p class="mt-2 text-sm text-zinc-500">Your donation has been submitted.</p>
                            <button
                                type="button"
                                x-on:click="submitted = false"
                                class="mt-8 rounded-xl bg-zinc-100 px-6 py-3 text-sm font-semibold text-zinc-600 transition hover:bg-zinc-200"
                            >
                                Back to form
                            </button>
                        </div>

                        {{-- Form state --}}
                        <div x-show="! submitted" class="min-h-[610px] space-y-4 px-4 pb-5 pt-12 text-sm">
                            {{-- Frequency toggle --}}
                            @if($allowMonthly)
                            <div data-preview-frequency class="flex rounded-xl bg-zinc-100 p-1">
                                <button
                                    type="button"
                                    x-on:click="frequency = 'one_time'; selectedAmount = @js($defaultAmount)"
                                    x-bind:class="frequency === 'one_time' ? 'bg-white text-blue-600 shadow-sm' : 'text-zinc-500 hover:text-zinc-700'"
                                    class="flex-1 rounded-lg px-3 py-2.5 text-center font-semibold transition-all"
                                >
                                    One-time
                                </button>
                                <button
                                    type="button"
                                    x-on:click="frequency = 'monthly'; selectedAmount = @js($defaultAmount)"
                                    x-bind:class="frequency === 'monthly' ? 'bg-white text-blue-600 shadow-sm' : 'text-zinc-500 hover:text-zinc-700'"
                                    class="flex-1 rounded-lg px-3 py-2.5 text-center font-semibold transition-all"
                                >
                                    <span class="inline-flex items-center gap-1">
                                        <span class="text-red-400">&hearts;</span>
                                        Monthly
                                    </span>
                                </button>
                            </div>
                            @endif

                            {{-- Title --}}
                            <h2 class="text-center text-xs font-semibold text-zinc-800">{{ $title }}</h2>

                            {{-- Suggested amounts as pill buttons --}}
                            @if($showSuggested)
                            <div data-preview-suggested class="grid grid-cols-3 gap-2">
                                <template x-for="amount in amounts" :key="amount">
                                    <button
                                        type="button"
                                        x-on:click="selectedAmount = amount"
                                        x-bind:class="Number(selectedAmount) === amount ? 'bg-blue-600 text-white shadow-sm shadow-blue-200' : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 hover:text-zinc-800'"
                                        class="rounded-lg px-1 py-2.5 text-center text-sm font-bold transition-all active:scale-95"
                                    >
                                        <span x-text="'RM ' + Number(amount).toLocaleString('en-MY')"></span>
                                    </button>
                                </template>
                            </div>
                            @endif

                            {{-- Custom amount input --}}
                            @if($showAmountInput)
                            <div data-preview-amount-input class="flex items-center rounded-xl border-2 border-zinc-200 bg-white px-4 py-3 transition focus-within:border-blue-400 focus-within:ring-4 focus-within:ring-blue-100">
                                <span class="text-sm font-semibold text-zinc-400">RM</span>
                                <input
                                    x-model.number="selectedAmount"
                                    type="number"
                                    min="1"
                                    step="1"
                                    class="min-w-0 flex-1 border-0 bg-transparent px-2 text-center text-2xl font-bold text-blue-600 outline-none placeholder:text-zinc-300 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                />
                                <span class="inline-flex items-center gap-1 text-sm font-semibold text-zinc-400">
                                    MYR
                                    <svg class="size-3" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 011.06 0L10 11.94l3.72-3.72a.75.75 0 111.06 1.06l-4.25 4.25a.75.75 0 01-1.06 0L5.22 9.28a.75.75 0 010-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </div>
                            @endif

                            @if($showDedication)
                            <label data-preview-dedication class="flex items-center gap-2 text-sm text-zinc-600">
                                <span class="size-5 rounded border border-zinc-300 bg-white"></span>
                                <span>Dedicate this donation</span>
                            </label>
                            @endif

                            @if($showComment)
                            <button
                                type="button"
                                data-preview-comment
                                class="text-sm font-medium text-zinc-600 underline underline-offset-2"
                            >
                                Add comment
                            </button>
                            @endif

                            {{-- CTA Button --}}
                            <button
                                type="button"
                                x-on:click="submitted = true"
                                class="w-full rounded-xl bg-blue-600 px-6 py-3 text-sm font-bold text-white shadow-lg shadow-blue-200 transition-all hover:bg-blue-700 hover:shadow-xl hover:shadow-blue-200 active:scale-[0.98]"
                            >
                                {{ $config['submit_text'] ?? 'Donate and Support' }}
                            </button>
                        </div>
                    </div>

                    {{-- Home indicator --}}
                    <div class="flex justify-center pb-1.5 pt-5">
                        <div class="h-1.5 w-28 rounded-full bg-zinc-700"></div>
                    </div>
                </div>
            </div>
        @elseif($type === 'popup')
            <div class="relative w-full max-w-sm rounded-xl bg-white p-6 shadow-lg ring-1 ring-zinc-200">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-zinc-900">Popup Preview</h3>
                    <button type="button" class="text-zinc-400 hover:text-zinc-600">&times;</button>
                </div>
                <p class="text-sm text-zinc-500">Popup configuration coming soon.</p>
            </div>
        @endif
    </div>
</div>
