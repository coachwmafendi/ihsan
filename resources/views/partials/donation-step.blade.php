{{-- Register donationStep Alpine component early via alpine:init to avoid timing race
     in cross-origin iframes where wire:effects scripts may run after Alpine initializes --}}
<script>
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && !Alpine._donationStepRegistered) {
            Alpine._donationStepRegistered = true;
            Alpine.data('donationStep', (initialFirstName = '', initialLastName = '', initialEmail = '', initialPhone = '', connectedStripeAccountId = null, initialMinimumAmount = 5, initialAmount = 5, initialStep = 1, initialFrequency = 'one_time', initialCurrency = 'myr', initialOneTimeAmounts = [], initialMonthlyAmounts = [], initialFeeConfig = {myr: 0.50, 'usd': 0.30, 'sgd': 0.50}, initialCoverFee = true, initialIsEmbed = false, initialIsPopup = false, initialCurrencySymbol = 'RM', initialDonationPublicId = null, initialRedirectUrl = '', initialIsPublicPage = false, initialRaisedAmount = 0, initialTargetAmount = 0, initialPaymentGateway = 'stripe') => {
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
                    donorFirstName: initialFirstName,
                    donorLastName: initialLastName,
                    donorEmail: initialEmail,
                    donorPhone: initialPhone,
                    minimumAmount: initialMinimumAmount,
                    feeConfig: initialFeeConfig,
                    coverFee: initialCoverFee,
                    isEmbed: initialIsEmbed,
                    isPopup: initialIsPopup,
                    isPublicPage: initialIsPublicPage,
                    donationPublicId: initialDonationPublicId,
                    redirectUrl: initialRedirectUrl,
                    campaignPublicId: '',
                    raisedAmount: initialRaisedAmount,
                    targetAmount: initialTargetAmount,
                    paymentGateway: initialPaymentGateway,
                    processing: false,
                    currentStep: initialStep > 1 ? initialStep : 1,
                    stepErrors: {},
                    cardError: '',

                    get feeRate() { return this.feeConfig[this.currency]?.percent ?? 0.055; },
                    get fixedFee() { return this.feeConfig[this.currency]?.fixed ?? 1.00; },
                    get estimatedFeeAmount() {
                        const amount = parseFloat(this.amount) || 0;
                        if (amount <= 0) return '0.00';
                        return (amount * this.feeRate + this.fixedFee).toFixed(2);
                    },
                    get currentAmounts() { return this.frequency === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts; },
                    get progressWidth() {
                        if (!this.targetAmount || this.targetAmount <= 0) return 0;
                        const percent = Math.round((this.raisedAmount / this.targetAmount) * 100);
                        return percent > 0 ? Math.max(2, Math.min(100, percent)) : 0;
                    },
                    get donorName() {
                        return `${this.donorFirstName || ''} ${this.donorLastName || ''}`.trim() || 'Friend';
                    },
                    formatCurrency(value) { return Number(value || 0).toLocaleString('en', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },

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
                        if (!this.donorFirstName.trim()) { this.stepErrors.firstName = 'First name is required.'; valid = false; }
                        if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.donorEmail)) { this.stepErrors.email = 'Please enter a valid email address.'; valid = false; }
                        return valid;
                    },
                    mountPaymentElement() {
                        const container = document.getElementById('payment-element');
                        if (!container) return;

                        if (paymentElement) {
                            paymentElement.unmount();
                            paymentElement = null;
                        }

                        if (elements) {
                            elements = null;
                        }

                        container.innerHTML = '';

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
                                    fontSizeBase: '15px',
                                },
                            },
                        });
                        paymentElement = elements.create('payment', {
                            layout: 'tabs',
                            wallets: {
                                link: 'never',
                            },
                            defaultValues: {
                                billingDetails: {
                                    name: `${this.donorFirstName} ${this.donorLastName}`.trim() || undefined,
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
                        if (this.isEmbed && ! this.isPublicPage && this.currentStep === 1) {
                            window.parent.postMessage({ type: 'ihsan:step-continue', amount: this.amount, frequency: this.frequency, currency: this.currency, coverFee: this.coverFee ? 1 : 0 }, '*');
                            return;
                        }
                        this.currentStep++;
                        if (this.currentStep === 2) this.trackInitiateCheckout();
                        if (this.currentStep === 3 && this.paymentGateway === 'stripe') this.$nextTick(() => this.mountPaymentElement());
                    },
                    trackInitiateCheckout() {
                        if (this._initiateSent) return;
                        this._initiateSent = true;
                        if (typeof window.IhsanTrack !== 'function') return;
                        const amountNumber = parseFloat(this.amount);
                        if (!Number.isFinite(amountNumber) || amountNumber <= 0) return;
                        window.IhsanTrack('InitiateCheckout', {
                            value: amountNumber,
                            currency: this.currency.toUpperCase(),
                            content_type: 'product',
                            contents: [{ id: 'donation', quantity: 1, item_price: amountNumber }],
                        });
                        if (this.$wire && typeof this.$wire.trackServerInitiateCheckout === 'function') {
                            this.$wire.trackServerInitiateCheckout();
                        }
                    },
                    trackPurchase() {
                        if (typeof window.IhsanTrack !== 'function') return;
                        if (!this.donationPublicId) return;
                        const amountNumber = parseFloat(this.amount);
                        if (!Number.isFinite(amountNumber) || amountNumber <= 0) return;
                        window.IhsanTrack('Purchase', {
                            value: amountNumber,
                            currency: this.currency.toUpperCase(),
                            content_type: 'product',
                            contents: [{ id: 'donation', quantity: 1, item_price: amountNumber }],
                        }, { eventID: 'purchase_' + this.donationPublicId });
                    },
                    prevStep() { if (this.currentStep > 1) this.currentStep--; },
                    waitForReadyPaint() {
                        return new Promise((resolve) => {
                            requestAnimationFrame(() => {
                                requestAnimationFrame(resolve);
                            });
                        });
                    },
                    async init() {
                        this.campaignPublicId = this.$el.dataset.campaignPublicId || '';
                        this.raisedAmount = parseFloat(this.$wire.campaignCollectedAmount) || 0;
                        this.targetAmount = parseFloat(this.$wire.campaignTargetAmount) || 0;

                        this.$wire.on('amount-updated', ({ amount }) => { this.setAmount(amount); });
                        this.$wire.on('currency-updated', ({ currency, symbol, amount, oneTimeAmounts, monthlyAmounts }) => {
                            if (currency) this.currency = currency;
                            this.currencySymbol = symbol;
                            if (oneTimeAmounts) this.oneTimeAmounts = oneTimeAmounts;
                            if (monthlyAmounts) this.monthlyAmounts = monthlyAmounts;
                            const amounts = this.frequency === 'monthly' ? this.monthlyAmounts : this.oneTimeAmounts;
                            this.setAmount(amount ?? (amounts.length > 0 ? amounts[0] : this.amount));
                        });

                        window.addEventListener('message', (event) => this.handleChipMessage(event));

                        stripe = connectedStripeAccountId
                            ? Stripe(window.stripePublishableKey, { stripeAccount: connectedStripeAccountId })
                            : Stripe(window.stripePublishableKey);

                        if (this.isPopup || this.isEmbed) {
                            await this.waitForReadyPaint();

                            requestAnimationFrame(() => {
                                window.parent.postMessage({ type: 'ihsan:donation-ready' }, '*');
                            });
                        }
                    },
                    finishSuccess() {
                        this.processing = false;
                        this.trackPurchase();

                        if (this.$wire.campaignCollectedAmount !== undefined) {
                            this.raisedAmount = parseFloat(this.$wire.campaignCollectedAmount) || 0;
                        }

                        this.currentStep = 'success';

                        if (this.campaignPublicId && window.parent !== window) {
                            window.parent.postMessage({ type: 'ihsan:donation-success', campaignPublicId: this.campaignPublicId }, '*');
                        }

                        if (this.redirectUrl && ! this.isEmbed) {
                            setTimeout(() => { window.location.href = this.redirectUrl; }, 1500);
                        }
                    },
                    openChipCheckout(url) {
                        // CHIP redirect checkout URLs set X-Frame-Options / CSP that prevents
                        // embedding in an iframe, so we must use a popup or a full-page redirect.
                        const width = Math.min(520, window.screen.availWidth);
                        const height = Math.min(820, window.screen.availHeight);
                        const left = Math.round((window.screen.availWidth - width) / 2);
                        const top = Math.round((window.screen.availHeight - height) / 2);
                        const features = 'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes,status=yes';

                        const popup = window.open(url, 'chipCheckout', features);

                        if (popup) {
                            popup.focus();
                            return;
                        }

                        // Fallback if the popup was blocked.
                        window.location.href = url;
                    },
                    async finalizeChip() {
                        this.processing = true;
                        try { await this.$wire.confirmChipPayment(this.donationPublicId); } catch (e) {
                            // Server finalization failure should not block the success UX.
                        }
                        this.donationPublicId = this.$wire.donationPublicId;
                        this.finishSuccess();
                    },
                    handleChipMessage(event) {
                        if (! event.data || typeof event.data !== 'object') return;

                        if (event.data.type === 'chip:payment:success') {
                            if (event.data.donationId) {
                                this.donationPublicId = event.data.donationId;
                            }
                            this.finalizeChip();
                            return;
                        }

                        if (event.data.type === 'chip:payment:failure' || event.data.type === 'chip:payment:cancel') {
                            this.processing = false;
                            this.currentStep = 'error';
                            this.cardError = 'Payment was not completed. Please try again.';
                        }
                    },
                    async handleSubmit() {
                        if (this.processing) return;
                        this.processing = true;
                        this.cardError = '';

                        this.$wire.$set('frequency', this.frequency, false);
                        this.$wire.$set('amount', this.amount, false);
                        this.$wire.$set('coverFee', this.coverFee, false);
                        this.$wire.$set('firstName', this.donorFirstName, false);
                        this.$wire.$set('lastName', this.donorLastName, false);
                        this.$wire.$set('email', this.donorEmail, false);
                        this.$wire.$set('phone', this.donorPhone, false);

                        let submitResponse;
                        try { submitResponse = await this.$wire.submit(); } catch (e) { this.processing = false; this.currentStep = 'error'; this.cardError = 'Unable to start payment. Please try again.'; return; }
                        if (! submitResponse) { this.processing = false; this.currentStep = 'error'; this.cardError = 'Unable to start payment. Please try again.'; return; }

                        if (String(submitResponse).startsWith('http')) {
                            this.donationPublicId = this.$wire.donationPublicId;
                            this.openChipCheckout(submitResponse);
                            return;
                        }

                        const clientSecret = submitResponse;
                        const { error: submitError } = await elements.submit();
                        if (submitError) { this.processing = false; this.currentStep = 'error'; this.cardError = submitError.message; return; }

                        const paymentIntentId = clientSecret.split('_secret_')[0] ?? null;
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
                        if (paymentIntentId) {
                            try {
                                await this.$wire.confirmPayment(paymentIntentId);
                            } catch (e) {
                                // Server finalization failure should not block the success UX.
                            }
                        }
                        this.donationPublicId = this.$wire.donationPublicId;
                        this.finishSuccess();
                    },
                };
            });
        }
    });
</script>
