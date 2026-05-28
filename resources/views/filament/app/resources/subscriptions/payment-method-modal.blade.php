<div
    x-data="stripePaymentMethod()"
    x-init="init"
>
    <div class="mb-4">
        <div
            id="stripe-card-element"
            class="rounded-lg border border-gray-300 p-3"
        ></div>
        <div
            id="stripe-card-errors"
            class="mt-2 text-sm text-danger-600"
        ></div>
    </div>

    <div
        x-show="loading"
        class="mb-4 text-sm text-gray-500"
    >
        Processing...
    </div>

    <div
        x-show="success"
        class="mb-4 rounded-lg bg-success-50 p-3 text-sm text-success-700"
    >
        Payment method saved successfully.
    </div>

    <button
        type="button"
        @click="submit"
        x-show="!success && !loading"
        class="inline-flex items-center justify-center gap-2 rounded-lg border border-transparent bg-gray-800 px-4 py-2 text-sm font-semibold text-white shadow-sm transition duration-75 hover:bg-gray-700 focus:ring-2 focus:ring-gray-500"
    >
        Save Card
    </button>

    <script>
        function stripePaymentMethod() {
            return {
                clientSecret: @js($this->paymentClientSecret),
                loading: false,
                success: false,
                stripe: null,
                elements: null,

                init() {
                    if (!this.clientSecret) return;

                    this.stripe = Stripe('{{ config('services.stripe.key') }}');

                    this.elements = this.stripe.elements({
                        clientSecret: this.clientSecret,
                        appearance: {
                            theme: 'stripe',
                        },
                    });

                    const card = this.elements.create('payment');
                    card.mount('#stripe-card-element');

                    card.on('change', (event) => {
                        const errorEl = document.getElementById('stripe-card-errors');
                        errorEl.textContent = event.error ? event.error.message : '';
                    });
                },

                async submit() {
                    this.loading = true;
                    this.success = false;

                    const { setupIntent, error } = await this.stripe.confirmSetup({
                        elements: this.elements,
                        redirect: 'if_required',
                    });

                    if (error) {
                        document.getElementById('stripe-card-errors').textContent = error.message;
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
