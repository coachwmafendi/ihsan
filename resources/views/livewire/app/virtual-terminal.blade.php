{{-- resources/views/livewire/app/virtual-terminal.blade.php --}}
<div x-data="vtPayment()" x-init="initStripe(@js($this->stripePublishableKey))" class="space-y-8">
    {{-- Page Header --}}
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">
            Virtual Terminal
            @if ($this->organization)
                <span class="text-slate-400">— {{ $this->organization->name }}</span>
            @endif
        </h1>
        <p class="mt-1 text-sm text-slate-500">Use the Virtual Terminal to process an in-person or over the phone donation.</p>
    </div>

    {{-- Flash Messages --}}
    @if ($flash)
        <div class="rounded-lg border p-4 {{ $flash['type'] === 'success' ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800' }}">
            {{ $flash['message'] }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
        {{-- Left column: form --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Campaign --}}
            <x-ui.card title="Campaign">
                <x-ui.select id="campaign_id" wire:model.live="formData.campaign_id" class="block w-full">
                    <flux:select.option value="">Select a campaign</flux:select.option>
                    @foreach ($this->campaigns as $id => $title)
                        <flux:select.option value="{{ $id }}">{{ $title }}</flux:select.option>
                    @endforeach
                </x-ui.select>
                @error('formData.campaign_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </x-ui.card>

            {{-- Donation --}}
            <x-ui.card title="Donation">
                <div class="space-y-4">
                    <div>
                        <label for="frequency" class="mb-1 block text-sm font-medium text-slate-700">Donation frequency</label>
                        <x-ui.select id="frequency" wire:model.live="formData.frequency" class="block w-full">
                            <flux:select.option value="once">Once</flux:select.option>
                            <flux:select.option value="monthly">Monthly</flux:select.option>
                        </x-ui.select>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="amount" class="mb-1 block text-sm font-medium text-slate-700">Amount</label>
                            <div class="flex">
                                <input
                                    id="amount"
                                    type="text"
                                    inputmode="decimal"
                                    pattern="[0-9]+(\.[0-9]{1,2})?"
                                    oninput="this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1').replace(/^(\d{0,5})(\.\d{0,2})?.*$/, '$1$2')"
                                    onblur="if (this.value !== '') { this.value = Number(this.value).toFixed(2); $wire.set('formData.amount', this.value) }"
                                    wire:model.live="formData.amount"
                                    class="flex-1 rounded-l-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                    placeholder="0.00"
                                />
                                @if (count($this->acceptedCurrencies) > 1)
                                    <div class="relative">
                                        <select
                                            aria-label="Currency"
                                            wire:model.live="formData.currency"
                                            class="block appearance-none rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 py-2.5 pl-3 pr-8 text-sm font-medium text-slate-600 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                                        >
                                            @foreach ($this->acceptedCurrencies as $currency)
                                                <option value="{{ $currency }}">{{ strtoupper($currency) }}</option>
                                            @endforeach
                                        </select>
                                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                                            <x-heroicon-o-chevron-down class="size-3.5" />
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">
                                        {{ $this->getCurrency() }}
                                    </span>
                                @endif
                            </div>
                            @error('formData.amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="scheduled_for" class="mb-1 block text-sm font-medium text-slate-700">Scheduled for</label>
                            <input
                                type="date"
                                id="scheduled_for"
                                wire:model="formData.scheduled_for"
                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                            />
                        </div>
                    </div>
                </div>
            </x-ui.card>

            {{-- Supporter --}}
            <x-ui.card title="Supporter">
                @if ($this->preloadedSupporter)
                    <div class="mb-4 rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 flex-1 text-left">
                                <p class="text-xs text-yellow-800">The donation will be processed for an existing supporter.</p>
                                <div class="mt-3 space-y-1">
                                    <p class="truncate text-sm font-semibold text-slate-900">{{ $this->preloadedSupporter->name }}</p>
                                    <p class="break-all text-xs text-slate-500">{{ $this->preloadedSupporter->email }}</p>
                                    @if ($this->preloadedSupporter->donations()->exists())
                                        <p class="text-xs text-slate-500">Last donated {{ $this->preloadedSupporter->donations()->latest()->first()->created_at->diffForHumans() }}</p>
                                    @endif
                                    <p class="text-xs text-slate-500">ID: {{ $this->preloadedSupporter->public_id }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                wire:click="clearPreloadedSupporter"
                                aria-label="Clear supporter"
                                class="text-slate-400 hover:text-slate-600"
                            >
                                <x-heroicon-o-x-mark class="size-5" />
                            </button>
                        </div>
                    </div>
                @endif

                @if ($this->searchedDonor && ! $this->preloadedSupporter)
                    <div class="mb-4 rounded-lg border border-blue-200 bg-blue-50 p-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs text-blue-800">We found an existing supporter with this email.</p>
                                <p class="mt-1 text-sm font-semibold text-slate-900">{{ $this->searchedDonor->name }}</p>
                            </div>
                            <button
                                type="button"
                                wire:click="loadSearchedDonor"
                                class="text-sm font-medium text-teal-600 hover:text-teal-700"
                            >
                                Load details
                            </button>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label for="first_name" class="mb-1 block text-sm font-medium text-slate-700">First name</label>
                        <input
                            id="first_name"
                            type="text"
                            wire:model="formData.first_name"
                            autocomplete="given-name"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                        @error('formData.first_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="last_name" class="mb-1 block text-sm font-medium text-slate-700">Last name</label>
                        <input
                            id="last_name"
                            type="text"
                            wire:model="formData.last_name"
                            autocomplete="family-name"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                        />
                        @error('formData.last_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="mt-4">
                    <label for="email" class="mb-1 block text-sm font-medium text-slate-700">Receipt email</label>
                    <input
                        id="email"
                        type="email"
                        wire:model="formData.email"
                        wire:blur="searchDonorByEmail"
                        autocomplete="email"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 focus:border-teal-500 focus:outline-none focus:ring-1 focus:ring-teal-500"
                    />
                    @error('formData.email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </x-ui.card>

            {{-- Payment method --}}
            <x-ui.card title="Payment method">
                <div class="space-y-3">
                    @if ($this->preloadedSupporter && count($this->savedCards) > 0)
                        @foreach ($this->savedCards as $card)
                            <label class="flex cursor-pointer items-center gap-3">
                                <input
                                    type="radio"
                                    name="payment_method"
                                    value="{{ $card['id'] }}"
                                    wire:model.live="formData.payment_method"
                                    class="h-4 w-4 text-teal-600"
                                />
                                <div class="flex items-center gap-2 text-sm text-slate-700">
                                    <span class="text-slate-600">Existing debit/credit card</span>
                                    <x-payment-icon :brand="$card['brand']" class="h-6 w-auto rounded" />
                                    <span class="font-medium">{{ $card['brand'] }}</span>
                                    <span>&bull;&bull;{{ $card['last4'] }}</span>
                                    <span class="text-slate-400">Exp. {{ $card['exp_month'] }}/{{ $card['exp_year'] }}</span>
                                </div>
                            </label>
                        @endforeach
                    @endif

                    <label class="flex cursor-pointer items-center gap-3">
                        <input
                            type="radio"
                            name="payment_method"
                            value="new_card"
                            wire:model.live="formData.payment_method"
                            class="h-4 w-4 text-teal-600"
                        />
                        <span class="text-sm text-slate-700">New credit card</span>
                    </label>

                    <div
                        x-show="$wire.formData.payment_method === 'new_card'"
                        x-cloak
                        x-transition
                        x-init="$watch('$wire.formData.payment_method', v => { if(v === 'new_card') mountCard() }); if ($wire.formData.payment_method === 'new_card') { $nextTick(() => mountCard()) }"
                        class="mt-4 space-y-4"
                        wire:ignore
                    >
                        <div class="rounded-lg border border-slate-200 bg-white p-4">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Card details</label>
                            <div id="card-element" class="min-h-[44px] rounded-lg border border-slate-300 p-3"></div>
                            <div class="mt-2 text-sm text-red-600" role="alert" x-text="errorMessage"></div>
                        </div>
                    </div>
                </div>
            </x-ui.card>

            {{-- Transaction costs --}}
            <x-ui.card title="Transaction costs">
                <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm text-slate-700">
                        Estimated costs for the selected payment method:
                        <span class="font-semibold text-slate-900">{{ $this->getProcessingFeeEstimate() }}</span>
                    </p>
                </div>
            </x-ui.card>
        </div>

        {{-- Right column: summary --}}
        <div class="lg:col-span-1">
            <div class="sticky top-8 rounded-xl border border-slate-200 bg-slate-50 p-6">
                <h2 class="mb-4 text-lg font-semibold text-slate-900">Summary</h2>
                <div class="space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-slate-600">{{ $this->formData['frequency'] === 'monthly' ? 'Monthly donation' : 'One-time donation' }}</span>
                        <span class="text-slate-900">{{ $this->getTotalAmount() }}</span>
                    </div>
                    <div class="flex justify-between gap-x-4 border-t border-slate-200 pt-3 text-sm font-semibold">
                        <span class="text-slate-900">Total donation amount</span>
                        <span class="text-slate-900">{{ $this->getTotalAmount() }}</span>
                    </div>
                </div>
                <button
                    type="button"
                    wire:loading.attr="disabled"
                    x-on:click="submitPayment()"
                    :disabled="processing ||
                        !$wire.formData.amount ||
                        !$wire.formData.campaign_id ||
                        !$wire.formData.first_name ||
                        !$wire.formData.last_name ||
                        !$wire.formData.email"
                    class="mt-6 w-full rounded-lg bg-slate-900 px-4 py-3 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    <span x-show="!processing">Make a donation</span>
                    <span x-show="processing">Processing...</span>
                </button>
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
                },

                async mountCard() {
                    if (this.cardMounted) return;
                    await new Promise(r => setTimeout(r, 100));
                    const container = document.getElementById('card-element');
                    if (!container) {
                        this.errorMessage = 'Could not load card form. Please try again.';
                        return;
                    }
                    try {
                        this.cardElement.unmount();
                    } catch (e) {}
                    this.cardElement.mount('#card-element');
                    this.cardMounted = true;
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
                        await $wire.processDonation();
                    } catch (e) {
                        this.errorMessage = 'Payment failed. Please try again.';
                    }
                    this.processing = false;
                }
            };
        }

        document.addEventListener('livewire:init', () => {
            Livewire.on('vt-error', ({ message }) => {
                // Allow Alpine to show the error
                setTimeout(() => {
                    const el = document.querySelector('[x-data="vtPayment()"]');
                    if (el && el.__x) {
                        el.__x.processing = false;
                    }
                }, 100);
            });
        });
    </script>
</div>
