@php
    use App\Models\Campaign;

    $campaigns = $this->getCampaigns();
    $preloadedSupporter = $this->preloadedSupporter;
@endphp

<x-filament-panels::page>
    <div x-data="vtPayment()" x-init="initStripe(@js($this->stripePublishableKey))" class="max-w-7xl mx-auto">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-950 dark:text-white">Virtual Terminal</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Use the Virtual Terminal to process an in-person or over the phone donation.
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Left column: form --}}
            <div class="lg:col-span-2 space-y-8">
                {{-- Campaign --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Campaign</h2>
                    <select
                        id="campaign_id"
                        wire:model.live="formData.campaign_id"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                        <option value="">Select a campaign</option>
                        @foreach ($campaigns as $id => $title)
                            <option value="{{ $id }}">{{ $title }}</option>
                        @endforeach
                    </select>
                    @error('formData.campaign_id')
                        <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                    @enderror
                </section>

                {{-- Donation --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Donation</h2>
                    <div class="space-y-4">
                        <div>
                            <label for="frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Donation frequency
                            </label>
                            <select
                                id="frequency"
                                wire:model.live="formData.frequency"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            >
                                <option value="once">Once</option>
                                <option value="monthly">Monthly</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Amount
                                </label>
                                <div class="flex">
                                    <input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        min="1"
                                        wire:model.live="formData.amount"
                                        class="flex-1 rounded-l-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                        placeholder="0.00"
                                    />
                                    <span class="inline-flex items-center rounded-r-lg border border-l-0 border-gray-300 bg-gray-50 px-3 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400">
                                        MYR
                                    </span>
                                </div>
                                @error('formData.amount')
                                    <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="scheduled_for" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Scheduled for
                                </label>
                                <input
                                    type="date"
                                    id="scheduled_for"
                                    wire:model="formData.scheduled_for"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                                />
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Supporter --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Supporter</h2>

                    @if ($preloadedSupporter)
                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4 mb-4 dark:border-yellow-900 dark:bg-yellow-900/20">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-xs text-yellow-800 dark:text-yellow-200">
                                        The donation will be processed for an existing supporter.
                                    </p>
                                    <p class="mt-2 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $preloadedSupporter->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $preloadedSupporter->email }}
                                    </p>
                                    @if ($preloadedSupporter->donations()->exists())
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            Last donated {{ $preloadedSupporter->donations()->latest()->first()->created_at->diffForHumans() }}
                                        </p>
                                    @endif
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                        ID: {{ $preloadedSupporter->public_id }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="clearPreloadedSupporter"
                                    aria-label="Clear supporter"
                                    class="text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                                >
                                    <x-heroicon-o-x-mark class="size-5" />
                                </button>
                            </div>
                        </div>
                    @endif

                    @if ($this->searchedDonor && ! $this->preloadedSupporter)
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 mb-4 dark:border-blue-900 dark:bg-blue-900/20">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-xs text-blue-800 dark:text-blue-200">
                                        We found an existing supporter with this email.
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ $this->searchedDonor->name }}
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    wire:click="loadSearchedDonor"
                                    class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                >
                                    Load details
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                First name
                            </label>
                            <input
                                id="first_name"
                                type="text"
                                wire:model="formData.first_name"
                                autocomplete="given-name"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                            @error('formData.first_name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Last name
                            </label>
                            <input
                                id="last_name"
                                type="text"
                                wire:model="formData.last_name"
                                autocomplete="family-name"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                            />
                            @error('formData.last_name')
                                <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="mt-4">
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Receipt email
                        </label>
                        <input
                            id="email"
                            type="email"
                            wire:model="formData.email"
                            wire:blur="searchDonorByEmail"
                            autocomplete="email"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        />
                        @error('formData.email')
                            <p class="mt-1 text-sm text-danger-600">{{ $message }}</p>
                        @enderror
                    </div>
                </section>

                {{-- Payment method --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Payment method</h2>
                    <div class="space-y-3">
                        @if ($preloadedSupporter && $preloadedSupporter->stripe_customer_id && count($this->savedCards) > 0)
                            @foreach ($this->savedCards as $card)
                                <label class="flex items-center gap-3 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="payment_method"
                                        value="{{ $card['id'] }}"
                                        wire:model="formData.payment_method"
                                        class="h-4 w-4 text-primary-600"
                                    />
                                    <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                        <span class="text-gray-600 dark:text-gray-400">Existing credit card</span>
                                        <span class="font-medium">{{ $card['brand'] }}</span>
                                        <span>&bull;&bull;{{ $card['last4'] }}</span>
                                        <span class="text-gray-400 dark:text-gray-500">Exp. {{ $card['exp_month'] }}/{{ $card['exp_year'] }}</span>
                                    </div>
                                </label>
                            @endforeach
                        @endif

                        <label class="flex items-center gap-3 cursor-pointer">
                            <input
                                type="radio"
                                name="payment_method"
                                value="new_card"
                                wire:model="formData.payment_method"
                                class="h-4 w-4 text-primary-600"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">New credit card</span>
                        </label>

                        <div
                            x-show="$wire.formData.payment_method === 'new_card'"
                            x-cloak
                            x-transition
                            x-init="$watch('$wire.formData.payment_method', v => { if(v === 'new_card') mountCard() })"
                            class="mt-4 space-y-4"
                        >
                            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Card details</label>

                                {{-- Skeleton loader --}}
                                <div x-show="cardLoading" class="animate-pulse space-y-2">
                                    <div class="h-10 bg-gray-200 rounded-lg dark:bg-gray-700"></div>
                                    <div class="flex gap-3">
                                        <div class="h-10 flex-1 bg-gray-200 rounded-lg dark:bg-gray-700"></div>
                                        <div class="h-10 w-24 bg-gray-200 rounded-lg dark:bg-gray-700"></div>
                                    </div>
                                </div>

                                {{-- Always in DOM so Stripe can mount; invisible only while loading --}}
                                <div :class="cardLoading ? 'invisible h-0 overflow-hidden' : ''">
                                    <div id="card-element" class="p-3 border border-gray-300 rounded-lg dark:border-gray-600"></div>
                                    <div class="text-danger-600 text-sm mt-2" role="alert" x-text="errorMessage"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Transaction costs --}}
                <section>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white mb-3">Transaction costs</h2>
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm text-gray-700 dark:text-gray-300">
                            Estimated costs for the selected payment method:
                            <span class="font-semibold text-gray-900 dark:text-white">{{ $this->getProcessingFeeEstimate() }}</span>
                        </p>
                    </div>
                </section>
            </div>

            {{-- Right column: summary --}}
            <div class="lg:col-span-1">
                <div class="sticky top-8 rounded-lg border border-gray-200 bg-gray-50 p-6 dark:border-gray-700 dark:bg-gray-800">
                    <h2 class="text-lg font-semibold text-gray-950 dark:text-white mb-4">Summary</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600 dark:text-gray-400">
                                {{ $this->formData['frequency'] === 'monthly' ? 'Monthly donation' : 'One-time donation' }}
                            </span>
                            <span class="text-gray-900 dark:text-white">{{ $this->getTotalAmount() }}</span>
                        </div>
                        <div class="border-t border-gray-200 pt-3 flex justify-between text-sm font-semibold dark:border-gray-700">
                            <span class="text-gray-900 dark:text-white">Total donation amount</span>
                            <span class="text-gray-900 dark:text-white">{{ $this->getTotalAmount() }}</span>
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:loading.attr="disabled"
                        wire:target="mountAction('processDonation')"
                        x-on:click="submitPayment()"
                        :disabled="processing ||
                            empty($wire.formData.amount) ||
                            empty($wire.formData.campaign_id) ||
                            empty($wire.formData.first_name) ||
                            empty($wire.formData.last_name) ||
                            empty($wire.formData.email)"
                        class="mt-6 w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-gray-800 disabled:opacity-50 disabled:cursor-not-allowed dark:bg-white dark:text-gray-950 dark:hover:bg-gray-100"
                    >
                        <span x-show="!processing">Make a donation</span>
                        <span x-show="processing">Processing...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://js.stripe.com/v3/"></script>
    <script>
        function vtPayment() {
            return {
                stripe: null,
                cardElement: null,
                processing: false,
                errorMessage: '',
                cardLoading: false,
                cardMounted: false,

                initStripe(publishableKey) {
                    if (!publishableKey) return;
                    this.stripe = Stripe(publishableKey);
                    this.cardElement = this.stripe.elements().create('card', {
                        style: {
                            base: {
                                fontSize: '14px',
                                color: '#1f2937',
                                '::placeholder': { color: '#9ca3af' }
                            }
                        }
                    });
                    this.cardElement.on('change', (event) => {
                        this.errorMessage = event.error ? event.error.message : '';
                    });
                    // If already on new_card, mount immediately
                    if ($wire.formData.payment_method === 'new_card') {
                        this.mountCard();
                    }
                },

                async mountCard() {
                    if (this.cardMounted) return;

                    this.cardLoading = true;
                    await this.$nextTick();

                    const container = document.getElementById('card-element');
                    if (!container) {
                        this.cardLoading = false;
                        this.errorMessage = 'Could not load card form. Please try again.';
                        return;
                    }

                    try {
                        this.cardElement.unmount();
                    } catch (e) {
                        // Not previously mounted, ignore
                    }
                    this.cardElement.mount('#card-element');
                    this.cardMounted = true;
                    this.cardLoading = false;
                },

                async submitPayment() {
                    const paymentMethod = $wire.formData.payment_method;

                    this.processing = true;
                    this.errorMessage = '';

                    try {
                        if (paymentMethod === 'new_card') {
                            const { paymentMethod: stripePM, error } = await this.stripe.createPaymentMethod({
                                type: 'card',
                                card: this.cardElement,
                            });

                            if (error) {
                                this.errorMessage = error.message;
                                this.processing = false;
                                return;
                            }

                            await $wire.set('formData.payment_method_id', stripePM.id);
                        }

                        await $wire.mountAction('processDonation');
                    } catch (e) {
                        this.errorMessage = 'Payment failed. Please try again.';
                    }
                    this.processing = false;
                }
            };
        }
    </script>
</x-filament-panels::page>
