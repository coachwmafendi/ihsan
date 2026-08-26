{{-- Register donationStep Alpine component early via alpine:init to avoid timing race
     in cross-origin iframes where wire:effects scripts may run after Alpine initializes --}}
<script>
    document.addEventListener('alpine:init', () => {
        if (typeof Alpine !== 'undefined' && !Alpine._donationStepRegistered) {
            Alpine._donationStepRegistered = true;
            Alpine.data('donationStep', (initialFirstName = '', initialLastName = '', initialEmail = '', initialPhone = '', connectedStripeAccountId = null, initialMinimumAmount = 5, initialAmount = 5, initialStep = 1, initialFrequency = 'one_time', initialCurrency = 'myr', initialOneTimeAmounts = [], initialMonthlyAmounts = [], initialFeeConfig = {myr: 0.50, 'usd': 0.30, 'sgd': 0.50}, initialCoverFee = true, initialIsEmbed = false, initialIsPopup = false, initialCurrencySymbol = 'RM', initialDonationPublicId = null, initialRedirectUrl = '', initialIsPublicPage = false, initialRaisedAmount = 0, initialTargetAmount = 0, initialPaymentGateway = 'stripe', initialChipPaymentMethods = [], initialChipPaymentMethod = 'card', initialFpxBanks = []) => {
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
                    chipPaymentMethods: initialChipPaymentMethods,
                    chipPaymentMethod: initialChipPaymentMethod,
                    chipFpxBankCode: '',
                    fpxBanks: initialFpxBanks,
                    chipPopup: null,
                    _chipBc: null,
                    _chipMessageHandled: false,
                    chipDirectPostSubmitted: false,
                    _chipDirectPostPollInterval: null,
                    _chipDirectPostPollTimeout: null,
                    processing: false,
                    currentStep: initialStep > 1 ? initialStep : 1,
                    stepErrors: {},
                    cardError: '',
                    stripeInitError: '',
                    upsell: null,
                    upsellShown: false,
                    upsellAccepted: false,
                    upsellOriginal: null,

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
                    // Upsell amounts read better without trailing zero cents.
                    formatCompactAmount(value) {
                        const number = Number(value || 0);
                        const decimals = Number.isInteger(number) ? 0 : 2;
                        return number.toLocaleString('en', { minimumFractionDigits: decimals, maximumFractionDigits: decimals });
                    },

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

                        // CHIP recurring donations can only be charged to cards.
                        if (freq === 'monthly' && this.paymentGateway === 'chip' && this.chipPaymentMethod === 'fpx') {
                            this.chipPaymentMethod = 'card';
                            this.chipFpxBankCode = '';
                        }

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

                        this.stripeInitError = '';

                        if (!stripe) {
                            this.stripeInitError = 'Payment system is not initialized. Please refresh the page or use a different browser.';
                            return;
                        }

                        try {
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
                                locale: 'en',
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
                        } catch (e) {
                            this.stripeInitError = 'Unable to load the payment form. Please refresh the page or disable content blockers.';
                            report?.(e);
                        }
                    },
                    async nextStep() {
                        if (this.currentStep === 1 && !this.validateStep1()) return;
                        if (this.currentStep === 2 && !this.validateStep2()) return;
                        if (typeof this.currentStep !== 'number' || this.currentStep >= 3) return;

                        if (this.currentStep === 1) {
                            if (await this.shouldShowUpsell()) {
                                this.upsellOriginal = this.amountNumber();
                                this.upsellShown = true;
                                this.currentStep = 'upsell';
                                return;
                            }

                            this.resumeAfterStepOne();
                            return;
                        }

                        this.currentStep++;
                        if (this.currentStep === 3 && this.paymentGateway === 'stripe') this.$nextTick(() => this.mountPaymentElement());
                    },
                    // The donor's amount and frequency only exist on the client
                    // until this point, so the offer has to be resolved now
                    // rather than baked in at render time.
                    async shouldShowUpsell() {
                        if (this.frequency !== 'one_time' || this.upsellShown) return false;
                        if (this.declinedRecently() || this.alreadyGivesMonthly()) return false;

                        const amount = this.amountNumber();
                        if (amount === null || amount <= 0) return false;

                        try {
                            this.upsell = await this.$wire.resolveMonthlyUpsell(amount, this.frequency);
                        } catch (e) {
                            // A failed lookup must never block the donation.
                            this.upsell = null;
                        }

                        return this.upsell !== null
                            && Array.isArray(this.upsell.offers)
                            && this.upsell.offers.length > 0;
                    },
                    resumeAfterStepOne() {
                        if (this.isEmbed && ! this.isPublicPage) {
                            window.parent.postMessage({
                                type: 'ihsan:step-continue',
                                amount: this.amount,
                                frequency: this.frequency,
                                currency: this.currency,
                                coverFee: this.coverFee ? 1 : 0,
                                upsell: this.upsellShown ? 1 : 0,
                            }, '*');
                            return;
                        }

                        this.currentStep = 2;
                        this.trackInitiateCheckout();
                    },
                    acceptUpsell(offer) {
                        this.frequency = 'monthly';
                        this.setAmount(offer);
                        this.upsellAccepted = true;

                        // CHIP recurring donations can only be charged to cards.
                        if (this.paymentGateway === 'chip' && this.chipPaymentMethod === 'fpx') {
                            this.chipPaymentMethod = 'card';
                            this.chipFpxBankCode = '';
                        }

                        this.resumeAfterStepOne();
                    },
                    declineUpsell() {
                        this.upsellAccepted = false;
                        this.rememberUpsellDecline();
                        this.resumeAfterStepOne();
                    },
                    upsellCooldownKey() {
                        return 'ihsan_upsell_declined_' + (this.campaignPublicId || 'default');
                    },
                    monthlyDonorKey() {
                        return 'ihsan_monthly_donor_' + (this.campaignPublicId || 'default');
                    },
                    // Asking an existing monthly supporter to "become a monthly
                    // supporter" reads badly. The donor's email only arrives at
                    // step 2, after the offer has to fire, so this remembers the
                    // plans started on this device instead.
                    alreadyGivesMonthly() {
                        try {
                            return window.localStorage.getItem(this.monthlyDonorKey()) !== null;
                        } catch (e) {
                            return false;
                        }
                    },
                    rememberMonthlyDonor() {
                        try {
                            window.localStorage.setItem(this.monthlyDonorKey(), String(Date.now()));
                        } catch (e) {
                            // Ignore: the donor may be offered again next visit.
                        }
                    },
                    // localStorage throws in sandboxed iframes and in Safari
                    // private mode. An uncaught throw here would take down the
                    // whole donation form, so every access is guarded.
                    declinedRecently() {
                        try {
                            const stored = window.localStorage.getItem(this.upsellCooldownKey());
                            if (!stored) return false;
                            const days = Number(this.upsell?.cooldownDays ?? 30);
                            return (Date.now() - Number(stored)) < days * 86400000;
                        } catch (e) {
                            return false;
                        }
                    },
                    rememberUpsellDecline() {
                        try {
                            window.localStorage.setItem(this.upsellCooldownKey(), String(Date.now()));
                        } catch (e) {
                            // Ignore: the donor simply sees the offer again next visit.
                        }
                    },
                    trackInitiateCheckout() {
                        if (this._initiateSent) return;
                        this._initiateSent = true;
                        if (typeof window.IhsanTrack !== 'function') return;
                        const amountNumber = parseFloat(this.amount);
                        if (!Number.isFinite(amountNumber) || amountNumber <= 0) return;
                        const eventId = typeof window.IhsanEventId === 'function' ? window.IhsanEventId('ic') : null;
                        window.IhsanTrack('InitiateCheckout', {
                            value: amountNumber,
                            currency: this.currency.toUpperCase(),
                            content_type: 'product',
                            contents: [{ id: 'donation', quantity: 1, item_price: amountNumber }],
                        }, eventId ? { eventID: eventId } : undefined);
                        if (this.$wire && typeof this.$wire.trackServerInitiateCheckout === 'function') {
                            this.$wire.trackServerInitiateCheckout(eventId);
                        }
                    },
                    trackPurchase() {
                        if (typeof window.IhsanTrack !== 'function') return;
                        if (!this.donationPublicId) return;
                        // After a redirect-back (3DS, FPX, CHIP) the form state is
                        // reset, so trust the server's finalized amount first.
                        const amountNumber = parseFloat(this.$wire.purchaseAmount ?? this.amount);
                        if (!Number.isFinite(amountNumber) || amountNumber <= 0) return;
                        const currency = (this.$wire.purchaseCurrency || this.currency).toUpperCase();
                        window.IhsanTrack('Purchase', {
                            value: amountNumber,
                            currency: currency,
                            content_type: 'product',
                            contents: [{ id: 'donation', quantity: 1, item_price: amountNumber }],
                        }, { eventID: 'purchase_' + this.donationPublicId });
                    },
                    prevStep() {
                        if (this.currentStep === 3) {
                            this.chipDirectPostSubmitted = false;
                            this.stopChipDirectPostPoll();
                        }
                        if (this.currentStep > 1) this.currentStep--;
                    },
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

                        this.handleChipReturnFromQueryParams();

                        const handledStripeReturn = await this.handleStripeReturnFromQueryParams();
                        if (handledStripeReturn) {
                            // Stripe finalized the donation from a redirect; skip normal init.
                            return;
                        }

                        this.$wire.on('chip-return', ({ status, donationId }) => {
                            if (! donationId) {
                                return;
                            }

                            if (status === 'success') {
                                this.donationPublicId = donationId;
                                this.finalizeChip();
                            } else {
                                this.processing = false;
                                this.currentStep = 'error';
                                this.cardError = 'Payment was not completed. Please try again.';
                            }
                        });

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

                        try {
                            stripe = connectedStripeAccountId
                                ? Stripe(window.stripePublishableKey, { stripeAccount: connectedStripeAccountId })
                                : Stripe(window.stripePublishableKey);
                        } catch (e) {
                            this.stripeInitError = 'Payment system failed to initialize. Please check that Stripe is configured or try a different browser.';

                            if (this.isPopup || this.isEmbed) {
                                await this.waitForReadyPaint();
                            }

                            return;
                        }

                        if (this.currentStep === 3 && this.paymentGateway === 'stripe') {
                            this.$nextTick(() => this.mountPaymentElement());
                        }

                        if (this.isPopup || this.isEmbed) {
                            await this.waitForReadyPaint();

                            requestAnimationFrame(() => {
                                window.parent.postMessage({ type: 'ihsan:donation-ready' }, '*');
                            });
                        }
                    },
                    finishSuccess() {
                        this.stopChipDirectPostPoll();
                        this.processing = false;
                        this.trackPurchase();

                        if (this.frequency === 'monthly') {
                            this.rememberMonthlyDonor();
                        }

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
                    startChipDirectPostPoll() {
                        this.stopChipDirectPostPoll();

                        if (! this.donationPublicId) {
                            return;
                        }

                        this._chipDirectPostPollInterval = setInterval(async () => {
                            try {
                                const finalized = await this.$wire.pollChipPaymentStatus(this.donationPublicId);

                                if (finalized) {
                                    this.stopChipDirectPostPoll();
                                    this.finishSuccess();
                                }
                            } catch (e) {
                                // Network/livewire errors are expected while the purchase is pending.
                            }
                        }, 3000);

                        this._chipDirectPostPollTimeout = setTimeout(() => {
                            this.stopChipDirectPostPoll();
                        }, 90000);
                    },
                    stopChipDirectPostPoll() {
                        if (this._chipDirectPostPollInterval) {
                            clearInterval(this._chipDirectPostPollInterval);
                            this._chipDirectPostPollInterval = null;
                        }

                        if (this._chipDirectPostPollTimeout) {
                            clearTimeout(this._chipDirectPostPollTimeout);
                            this._chipDirectPostPollTimeout = null;
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
                            this.chipPopup = popup;
                            popup.focus();
                            return;
                        }

                        // Fallback if the popup was blocked.
                        window.location.href = url;
                    },
                    closeChipPopup() {
                        if (this.chipPopup && !this.chipPopup.closed) {
                            try {
                                this.chipPopup.close();
                            } catch (e) {
                                // Ignore cross-origin restrictions.
                            }
                        }
                        this.chipPopup = null;
                    },
                    listenForChipReturn() {
                        if (! this.donationPublicId) {
                            return;
                        }

                        // Clean up any previous listener first.
                        if (this._chipBc) {
                            try {
                                this._chipBc.close();
                            } catch (e) {}
                            this._chipBc = null;
                        }

                        const donationId = this.donationPublicId;

                        if (typeof BroadcastChannel !== 'undefined') {
                            try {
                                this._chipBc = new BroadcastChannel('ihsan:chip:' + donationId);
                                this._chipBc.onmessage = (event) => {
                                    if (! event.data) {
                                        return;
                                    }
                                    this.handleChipMessage({ data: event.data });
                                };
                            } catch (e) {
                                this._chipBc = null;
                            }
                        }

                        const storageKey = 'ihsan:chip:' + donationId;
                        const storageHandler = (event) => {
                            if (event.key !== storageKey || ! event.newValue) {
                                return;
                            }

                            try {
                                const data = JSON.parse(event.newValue);
                                this.handleChipMessage({ data: data });
                            } catch (e) {}
                        };

                        if (this._chipStorageHandler) {
                            window.removeEventListener('storage', this._chipStorageHandler);
                        }
                        this._chipStorageHandler = storageHandler;
                        window.addEventListener('storage', storageHandler);
                    },
                    handleChipReturnFromQueryParams() {
                        if (typeof URLSearchParams === 'undefined') {
                            return;
                        }

                        const params = new URLSearchParams(window.location.search);
                        const status = params.get('chip_status');
                        const donationId = params.get('donation_id');

                        if (! status || ! donationId) {
                            return;
                        }

                        // Strip the query params so a refresh does not re-trigger the flow.
                        const cleanUrl = new URL(window.location.href);
                        cleanUrl.searchParams.delete('chip_status');
                        cleanUrl.searchParams.delete('donation_id');
                        window.history.replaceState({}, '', cleanUrl.toString());

                        this.donationPublicId = donationId;

                        if (status === 'success') {
                            this.finalizeChip();
                        } else if (status === 'failure' || status === 'cancelled' || status === 'cancel') {
                            this.processing = false;
                            this.currentStep = 'error';
                            this.cardError = 'Payment was not completed. Please try again.';
                        }
                    },
                    async handleStripeReturnFromQueryParams() {
                        if (typeof URLSearchParams === 'undefined') {
                            return false;
                        }

                        const params = new URLSearchParams(window.location.search);
                        const paymentIntentId = params.get('payment_intent');
                        const redirectStatus = params.get('redirect_status');

                        if (! paymentIntentId || ! redirectStatus) {
                            return false;
                        }

                        // Strip Stripe query params so a refresh does not re-trigger the flow.
                        const cleanUrl = new URL(window.location.href);
                        cleanUrl.searchParams.delete('payment_intent');
                        cleanUrl.searchParams.delete('payment_intent_client_secret');
                        cleanUrl.searchParams.delete('redirect_status');
                        window.history.replaceState({}, '', cleanUrl.toString());

                        if (redirectStatus !== 'succeeded') {
                            this.processing = false;
                            this.currentStep = 'error';
                            this.cardError = 'Payment was not completed. Please try again.';
                            return true;
                        }

                        this.processing = true;

                        try {
                            await this.$wire.confirmPayment(paymentIntentId);
                            this.donationPublicId = this.$wire.donationPublicId;
                            this.donorFirstName = this.$wire.firstName || this.donorFirstName;
                            this.donorLastName = this.$wire.lastName || this.donorLastName;
                            this.donorEmail = this.$wire.email || this.donorEmail;
                            this.donorPhone = this.$wire.phone || this.donorPhone;
                            this.finishSuccess();
                        } catch (e) {
                            this.processing = false;
                            this.currentStep = 'error';
                            this.cardError = 'We could not finalize your payment. Please contact support.';
                            report?.(e);
                        }

                        return true;
                    },

                    async finalizeChip() {
                        if (! this.donationPublicId) {
                            return;
                        }

                        this.processing = true;

                        try {
                            await this.$wire.confirmChipPayment(this.donationPublicId);
                        } catch (e) {
                            // Server finalization failure should not block the success UX.
                        }

                        this.donationPublicId = this.$wire.donationPublicId;
                        this.donorFirstName = this.$wire.firstName || this.donorFirstName;
                        this.donorLastName = this.$wire.lastName || this.donorLastName;
                        this.donorEmail = this.$wire.email || this.donorEmail;
                        this.donorPhone = this.$wire.phone || this.donorPhone;
                        this.finishSuccess();
                    },
                    handleChipMessage(event) {
                        if (! event.data || typeof event.data !== 'object') return;

                        // Guard against multiple notifications delivered through
                        // window.opener postMessage, BroadcastChannel and storage events.
                        if (this._chipMessageHandled) return;
                        if (event.data.donationId && event.data.donationId !== this.donationPublicId) return;
                        this._chipMessageHandled = true;

                        // Clean up the cross-tab listener once a result is received.
                        if (this._chipBc) {
                            try { this._chipBc.close(); } catch (e) {}
                            this._chipBc = null;
                        }

                        if (event.data.type === 'chip:payment:success') {
                            if (event.data.donationId) {
                                this.donationPublicId = event.data.donationId;
                            }
                            this.closeChipPopup();
                            this.finalizeChip();
                            return;
                        }

                        if (event.data.type === 'chip:payment:failure' || event.data.type === 'chip:payment:cancel') {
                            this.closeChipPopup();
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
                        this.$wire.$set('chipPaymentMethod', this.chipPaymentMethod, false);
                        this.$wire.$set('chipFpxBankCode', this.chipFpxBankCode || null, false);
                        this.$wire.$set('upsellShown', this.upsellShown, false);
                        this.$wire.$set('upsellAccepted', this.upsellAccepted, false);
                        this.$wire.$set('upsellOriginalAmount', this.upsellOriginal, false);

                        let submitResponse;
                        try { submitResponse = await this.$wire.submit(); } catch (e) { this.processing = false; this.currentStep = 'error'; this.cardError = 'Unable to start payment. Please try again.'; return; }
                        if (! submitResponse && ! this.$wire.chipDirectPostUrl) { this.processing = false; this.currentStep = 'error'; this.cardError = this.$wire.chipErrorMessage || 'Unable to start payment. Please try again.'; return; }

                        if (this.$wire.chipDirectPostUrl) {
                            const form = document.getElementById('chip-direct-post-form');
                            if (form) {
                                this.donationPublicId = this.$wire.donationPublicId;
                                form.action = this.$wire.chipDirectPostUrl;
                                this.chipDirectPostSubmitted = true;
                                this.processing = false;
                                this.startChipDirectPostPoll();
                                form.submit();
                            } else {
                                this.processing = false;
                                this.currentStep = 'error';
                                this.cardError = 'Unable to prepare card payment. Please try again.';
                            }
                            return;
                        }

                        if (String(submitResponse).startsWith('http')) {
                            this.donationPublicId = this.$wire.donationPublicId;
                            this.listenForChipReturn();
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
