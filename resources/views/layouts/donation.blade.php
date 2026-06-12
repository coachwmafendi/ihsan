<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
        <script src="https://js.stripe.com/v3/"></script>
        @livewireStyles
    </head>
    <body class="min-h-screen bg-[#eef1f6] text-slate-950 antialiased">
        {{ $slot }}

        @livewireScripts

        <script>
            window.stripePublishableKey = '{{ config('services.stripe.key') }}';
        </script>

        {{-- Register donationStep Alpine component early via alpine:init to avoid timing race
             in cross-origin iframes where wire:effects scripts may run after Alpine initializes --}}
        <script>
            document.addEventListener('alpine:init', () => {
                if (typeof Alpine !== 'undefined' && !Alpine._donationStepRegistered) {
                    Alpine._donationStepRegistered = true;
                    Alpine.data('donationStep', (initialName = '', initialEmail = '', initialPhone = '', connectedStripeAccountId = null, initialMinimumAmount = 5, initialAmount = 5, initialStep = 1, initialFrequency = 'one_time', initialCurrency = 'myr', initialOneTimeAmounts = [], initialMonthlyAmounts = [], initialFeeConfig = {myr: 0.50, usd: 0.30, sgd: 0.50}, initialCoverFee = true, initialIsEmbed = false, initialCurrencySymbol = 'RM') => {
                        let stripe = null;
                        let elements = null;
                        let paymentElement = null;

                        return {
                            amount: String(initialAmount ?? ''),
                            currency: initialCurrency,
                            currencySymbol: initialCurrencySymbol,
                            frequency: initialFrequency,
                            oneTimeAmounts: initialOneTimeAmounts,
                            monthlyAmounts: initialMonthlyAmounts,
                            donorName: initialName,
                            donorEmail: initialEmail,
                            donorPhone: initialPhone,
                            minimumAmount: initialMinimumAmount,
                            feeConfig: initialFeeConfig,
                            coverFee: initialCoverFee,
                            isEmbed: initialIsEmbed,
                            processing: false,
                            currentStep: initialStep > 1 ? initialStep : 1,
                            stepErrors: {},
                            cardError: '',

                            get fixedFee() { return this.feeConfig[this.currency] ?? 0.50; },
                            get currentAmounts() { return this.frequency === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts; },

                            amountNumber(value = this.amount) {
                                const parsed = parseFloat(value);
                                return Number.isFinite(parsed) ? parsed : null;
                            },
                            amountOptionKey(amt) { return `${this.currency}-${this.frequency}-${amt}`; },
                            isSelectedAmount(amt) {
                                const selected = this.amountNumber();
                                const option = this.amountNumber(amt);
                                return selected !== null && option !== null && selected === option;
                            },
                            setAmount(value) {
                                this.amount = String(value ?? '');
                                this.$nextTick(() => {
                                    if (this.$refs.amountInput && this.$refs.amountInput.value !== this.amount) {
                                        this.$refs.amountInput.value = this.amount;
                                    }
                                });
                            },
                            selectAmount(amt) { this.setAmount(amt); },
                            selectFrequency(freq) {
                                this.frequency = freq;
                                const amounts = freq === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts;
                                this.setAmount(amounts.length > 0 ? amounts[0] : this.amount);
                            },
                            launchHearts(event) {
                                const btn = event.currentTarget;
                                const rect = btn.getBoundingClientRect();
                                const color = btn.querySelector('span') ? getComputedStyle(btn.querySelector('span')).color : '#e11d48';
                                const colors = [color, '#f43f5e', '#fb7185', '#fda4af'];
                                for (let i = 0; i < 5; i++) {
                                    setTimeout(() => {
                                        const el = document.createElement('span');
                                        el.textContent = '♥';
                                        const size = 12 + Math.random() * 10;
                                        const startX = rect.left + rect.width * 0.2 + Math.random() * rect.width * 0.6;
                                        const driftX = (Math.random() - 0.5) * 50;
                                        const riseY = 60 + Math.random() * 60;
                                        const duration = 1000 + Math.random() * 600;
                                        el.style.cssText = `position:fixed;left:${startX}px;top:${rect.top}px;color:${colors[Math.floor(Math.random()*colors.length)]};font-size:${size}px;pointer-events:none;z-index:9999;user-select:none;`;
                                        document.body.appendChild(el);
                                        el.animate([
                                            { transform: 'translate(0,0) scale(1)', opacity: 1 },
                                            { transform: `translate(${driftX*.5}px,${-riseY*.5}px) scale(1.1)`, opacity: .8, offset: .4 },
                                            { transform: `translate(${driftX}px,${-riseY}px) scale(.5)`, opacity: 0 },
                                        ], { duration, easing: 'ease-out', fill: 'forwards' }).onfinish = () => el.remove();
                                    }, i * 80);
                                }
                            },
                            validateStep1() {
                                this.stepErrors = {};
                                const amt = parseFloat(this.amount);
                                if (!amt || amt < this.minimumAmount) { this.stepErrors.amount = 'Minimum amount is ' + this.minimumAmount + '.'; return false; }
                                if (amt > 100000) { this.stepErrors.amount = 'Amount cannot exceed 100,000.'; return false; }
                                return true;
                            },
                            validateStep2() {
                                this.stepErrors = {};
                                let valid = true;
                                if (!this.donorName.trim()) { this.stepErrors.name = 'Name is required.'; valid = false; }
                                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.donorEmail)) { this.stepErrors.email = 'Please enter a valid email address.'; valid = false; }
                                return valid;
                            },
                            mountPaymentElement() {
                                const container = document.getElementById('payment-element');
                                if (!container || container.hasChildNodes()) return;
                                const amount = Math.round(parseFloat(this.amount || 0) * 100);
                                const isRecurring = this.frequency === 'monthly';
                                elements = stripe.elements({
                                    mode: isRecurring ? 'subscription' : 'payment',
                                    amount: amount,
                                    currency: this.currency,
                                    setupFutureUsage: isRecurring ? 'off_session' : undefined,
                                    locale: 'ms',
                                    appearance: {
                                        theme: 'stripe',
                                        variables: {
                                            colorPrimary: '#0d9488',
                                            fontSizeBase: '16px',
                                        },
                                    },
                                });
                                paymentElement = elements.create('payment', {
                                    layout: 'tabs',
                                    defaultValues: {
                                        billingDetails: {
                                            name: this.donorName || undefined,
                                            email: this.donorEmail || undefined,
                                        },
                                    },
                                });
                                paymentElement.mount('#payment-element');
                                paymentElement.on('change', (e) => { this.cardError = e.error ? e.error.message : ''; });
                            },
                            nextStep() {
                                if (this.currentStep === 1 && !this.validateStep1()) return;
                                if (this.currentStep === 2 && !this.validateStep2()) return;
                                if (typeof this.currentStep !== 'number' || this.currentStep >= 3) return;
                                if (this.isEmbed && this.currentStep === 1) {
                                    window.parent.postMessage({ type: 'ihsan:step-continue', amount: this.amount, frequency: this.frequency, currency: this.currency, coverFee: this.coverFee ? 1 : 0 }, '*');
                                    return;
                                }
                                this.currentStep++;
                                if (this.currentStep === 3) this.$nextTick(() => this.mountPaymentElement());
                            },
                            prevStep() { if (this.currentStep > 1) this.currentStep--; },
                            async init() {
                                $wire.on('amount-updated', ({ amount }) => { this.setAmount(amount); });
                                $wire.on('currency-updated', ({ currency, symbol, amount, oneTimeAmounts, monthlyAmounts }) => {
                                    if (currency) this.currency = currency;
                                    this.currencySymbol = symbol;
                                    if (oneTimeAmounts) this.oneTimeAmounts = oneTimeAmounts;
                                    if (monthlyAmounts) this.monthlyAmounts = monthlyAmounts;
                                    const amounts = this.frequency === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts;
                                    this.setAmount(amount ?? (amounts.length > 0 ? amounts[0] : this.amount));
                                });
                                stripe = connectedStripeAccountId
                                    ? Stripe(window.stripePublishableKey, { stripeAccount: connectedStripeAccountId })
                                    : Stripe(window.stripePublishableKey);
                            },
                            async handleSubmit() {
                                if (this.processing) return;
                                this.processing = true;
                                this.cardError = '';
                                const { error: submitError } = await elements.submit();
                                if (submitError) { this.processing = false; this.currentStep = 'error'; this.cardError = submitError.message; return; }
                                $wire.$set('frequency', this.frequency, false);
                                $wire.$set('amount', this.amount, false);
                                $wire.$set('coverFee', this.coverFee, false);
                                $wire.$set('name', this.donorName, false);
                                $wire.$set('email', this.donorEmail, false);
                                $wire.$set('phone', this.donorPhone, false);
                                let clientSecret;
                                try { clientSecret = await $wire.submit(); } catch (e) { this.processing = false; this.currentStep = 'error'; this.cardError = 'Unable to start payment. Please try again.'; return; }
                                if (!clientSecret) { this.processing = false; this.currentStep = 'error'; this.cardError = 'Unable to start payment. Please try again.'; return; }
                                const { error: confirmError } = await stripe.confirmPayment({
                                    elements,
                                    clientSecret,
                                    confirmParams: {
                                        receipt_email: this.donorEmail,
                                        return_url: window.location.href,
                                    },
                                    redirect: 'if_required',
                                });
                                if (confirmError) { this.processing = false; this.currentStep = 'error'; this.cardError = confirmError.message; return; }
                                this.processing = false;
                                this.currentStep = 'success';
                            },
                        };
                    });
                }
            });
        </script>
    </body>
</html>
