<div
    x-data="paymentDetails({{ json_encode($clientSecret) }})"
    x-init="init"
    class="space-y-6"
>
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
        <select class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500">
            @php
                $frequencies = [
                    'one_time' => 'One Time',
                    'weekly' => 'Weekly',
                    'monthly' => 'Monthly',
                    'yearly' => 'Yearly',
                ];
                $currentInterval = $record->interval->value;
            @endphp
            @foreach ($frequencies as $value => $label)
                <option value="{{ $value }}" {{ $currentInterval === $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Starting On --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">Starting on</label>
        <select class="flex-1 rounded-lg border border-gray-300 py-2 px-3 text-sm focus:border-primary-500 focus:ring-primary-500">
            @php
                function ordinal(int $number): string {
                    $suffix = match (true) {
                        in_array($number % 100, [11, 12, 13]) => 'th',
                        $number % 10 === 1 => 'st',
                        $number % 10 === 2 => 'nd',
                        $number % 10 === 3 => 'rd',
                        default => 'th',
                    };
                    return $number.$suffix;
                }
                $currentDay = ($record->current_period_start ?? $record->created_at)?->format('j') ?? 19;
            @endphp
            @for ($day = 1; $day <= 31; $day++)
                <option value="{{ $day }}" {{ (int) $currentDay === $day ? 'selected' : '' }}>
                    {{ ordinal($day) }}
                </option>
            @endfor
        </select>
    </div>

    {{-- End Date --}}
    <div class="flex items-center gap-4">
        <label class="w-32 text-right text-sm font-medium text-gray-700">End date</label>
        <div class="relative flex-1">
            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                <x-heroicon-o-calendar class="w-4 h-4" />
            </span>
            <input
                type="date"
                value="{{ $record->created_at->copy()->addYear()->format('Y-m-d') }}"
                class="w-full rounded-lg border border-gray-300 py-2 pl-10 pr-3 text-sm focus:border-primary-500 focus:ring-primary-500"
            >
        </div>
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
                <input
                    type="radio"
                    name="payment_method"
                    value="current"
                    x-model="paymentMethod"
                    class="text-primary-600 focus:ring-primary-500"
                >
                <div class="flex items-center gap-2">
                    <span class="text-blue-600">
                        <x-heroicon-o-credit-card class="w-6 h-6" />
                    </span>
                    <span class="text-sm text-gray-600">VISA <span class="text-gray-400">•• 3397 • Exp. 07/26</span></span>
                </div>
            </label>

            <label class="flex items-center gap-3 cursor-pointer">
                <input
                    type="radio"
                    name="payment_method"
                    value="new"
                    x-model="paymentMethod"
                    class="text-primary-600 focus:ring-primary-500"
                >
                <span class="text-sm text-gray-600">New credit card</span>
            </label>
        </div>
    </div>

    {{-- New Card Input Section --}}
    <div
        x-show="paymentMethod === 'new'"
        x-cloak
        class="space-y-4 rounded-xl bg-gray-50 p-5"
    >
        <div>
            <label class="block text-sm font-semibold text-gray-900 mb-2">Card number</label>
            <div
                id="stripe-card-element"
                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3"
            ></div>
            <div
                id="stripe-card-errors"
                class="mt-2 text-sm text-red-600"
                x-text="cardError"
            ></div>
        </div>

        <div x-show="loading" class="text-sm text-gray-500">
            Processing...
        </div>

        <div x-show="success" x-cloak class="rounded-lg bg-green-50 p-3 text-sm text-green-700">
            Payment method saved successfully.
        </div>
    </div>

    {{-- Info Box --}}
    <div class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-800">
        The next installment of {{ \App\Support\Currency::symbol($record->currency) }}{{ number_format($record->amount + ($record->amount * 0.03 + 0.50), 2) }} (with costs covered) will run on {{ $record->current_period_end?->format('M j, Y, g:i A') ?? 'N/A' }}.
    </div>

    {{-- Actions --}}
    <div class="flex justify-end gap-3 pt-2">
        <button
            type="button"
            x-show="paymentMethod === 'new'"
            x-on:click="submit"
            x-bind:disabled="loading || !clientSecret"
            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
        >
            <span x-show="!loading">Save changes</span>
            <span x-show="loading">Processing...</span>
        </button>
        <button
            type="button"
            x-show="paymentMethod === 'current'"
            x-on:click="$dispatch('close-modal')"
            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
        >
            Save changes
        </button>
    </div>

    <script>
        function paymentDetails(clientSecret) {
            return {
                paymentMethod: 'current',
                loading: false,
                success: false,
                cardError: '',
                clientSecret: clientSecret,
                stripe: null,
                cardElement: null,

                init() {
                    this.$watch('paymentMethod', (value) => {
                        if (value === 'new') {
                            this.$nextTick(() => this.mountCardElement());
                        }
                    });
                },

                mountCardElement() {
                    if (!this.clientSecret) return;

                    const container = document.getElementById('stripe-card-element');
                    if (!container || container.hasChildNodes()) return;

                    this.stripe = Stripe('{{ config('services.stripe.key') }}');
                    const elements = this.stripe.elements({
                        clientSecret: this.clientSecret,
                        appearance: { theme: 'stripe' }
                    });

                    const card = elements.create('payment');
                    card.mount('#stripe-card-element');

                    card.on('change', (event) => {
                        this.cardError = event.error ? event.error.message : '';
                    });

                    this.cardElement = card;
                },

                async submit() {
                    this.loading = true;
                    this.success = false;
                    this.cardError = '';

                    const { setupIntent, error } = await this.stripe.confirmSetup({
                        elements: this.stripe.elements({ clientSecret: this.clientSecret }),
                        redirect: 'if_required',
                    });

                    if (error) {
                        this.cardError = error.message;
                        this.loading = false;
                        return;
                    }

                    await $wire.savePaymentMethod(setupIntent.payment_method);
                    this.success = true;
                    this.loading = false;
                },
            };
        }
    </script>
</div>
