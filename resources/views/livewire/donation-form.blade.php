@php
    $campaign = $element->campaign;
    $organization = $campaign->organization;
    $suggestedAmounts = $this->suggestedAmounts();
    $title = $this->config('title', 'Your most generous donation');
    $submitText = $this->config('submit_text', 'Donate and Support');
    $textColor = $this->config('text_color', '#212830');
    $backgroundColor = $this->config('background_color', '#FFFFFF');
    $iconColor = $this->config('icon_color', '#FF435A');
    $borderColor = $this->config('border_color', '#DEDFF3');
    $borderSize = (int) $this->config('border_size', 2);
    $borderRadius = (int) $this->config('border_radius', 6);
    $showShadow = (bool) $this->config('show_shadow', false);
    $allowMonthly = (bool) $this->config('allow_monthly', true);
    $showDedication = (bool) $this->config('show_dedication', true);
    $showComment = (bool) $this->config('show_comment', true);
    $isPopup = request()->query('popup');
@endphp

@if ($isPopup)
    <div class="p-6">
        <div class="mb-4 flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-md bg-teal-700 text-lg font-semibold text-white">
                {{ str($organization->name)->substr(0, 1)->upper() }}
            </div>
            <div>
                <p class="text-sm font-medium text-slate-500">{{ $organization->name }}</p>
                <h1 class="text-xl font-semibold tracking-normal text-slate-950">{{ $campaign->title }}</h1>
            </div>
        </div>

        @if ($campaign->has_target)
            @php
                $targetAmount = max((float) $campaign->target_amount, 1);
                $collectedAmount = (float) $campaign->collected_amount;
                $progress = min(100, round(($collectedAmount / $targetAmount) * 100));
            @endphp

            <div class="mb-4">
                <div class="mb-1.5 flex items-end justify-between gap-4 text-sm">
                    <span class="font-semibold text-slate-950">RM {{ number_format($collectedAmount, 2) }} raised</span>
                    <span class="text-slate-500">Goal RM {{ number_format($targetAmount, 2) }}</span>
                </div>
                <div class="h-1.5 rounded-full bg-slate-200">
                    <div class="h-1.5 rounded-full bg-teal-700" style="width: {{ $progress }}%"></div>
                </div>
            </div>
        @endif
@else
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8">
    <main class="mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-[minmax(0,1fr)_440px]">
        <section class="rounded-lg border border-white/70 bg-white/80 p-6 shadow-sm backdrop-blur sm:p-8">
            <div class="mb-8 flex items-center gap-4">
                <div class="flex size-14 items-center justify-center rounded-md bg-teal-700 text-xl font-semibold text-white">
                    {{ str($organization->name)->substr(0, 1)->upper() }}
                </div>
                <div>
                    <p class="text-base font-medium text-slate-500">{{ $organization->name }}</p>
                    <h1 class="text-3xl font-semibold tracking-normal text-slate-950 sm:text-4xl">{{ $campaign->title }}</h1>
                </div>
            </div>

            @if ($campaign->description)
                <p class="max-w-2xl text-lg/8 text-slate-600">{{ $campaign->description }}</p>
            @endif

            @if ($campaign->has_target)
                @php
                    $targetAmount = max((float) $campaign->target_amount, 1);
                    $collectedAmount = (float) $campaign->collected_amount;
                    $progress = min(100, round(($collectedAmount / $targetAmount) * 100));
                @endphp

                <div class="mt-8 max-w-2xl">
                    <div class="mb-2 flex items-end justify-between gap-4 text-base">
                        <span class="font-semibold text-slate-950">RM {{ number_format($collectedAmount, 2) }} raised</span>
                        <span class="text-slate-500">Goal RM {{ number_format($targetAmount, 2) }}</span>
                    </div>
                    <div class="h-2 rounded-full bg-slate-200">
                        <div class="h-2 rounded-full bg-teal-700" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            @endif
        </section>

        <section class="mx-auto w-full max-w-[440px] rounded-[32px] bg-slate-200 p-3 shadow-xl shadow-slate-300/70 ring-1 ring-slate-300">
            <div class="mx-auto mb-3 h-1.5 w-24 rounded-full bg-slate-300"></div>
@endif

            <div
                class="{{ $showShadow ? 'shadow-xl' : 'shadow-sm' }} {{ $isPopup ? '' : 'p-6' }}"
                style="background-color: {{ $backgroundColor }}; color: {{ $textColor }}; border: {{ $borderSize }}px solid {{ $borderColor }}; border-radius: {{ $isPopup ? $borderRadius : $borderRadius + 10 }}px;"
            >
                <div x-data="donationForm()">
                    <div x-show="!processing && !success && !error" x-cloak>
                        <form class="space-y-4" @submit.prevent="handleSubmit">
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    type="button"
                                    wire:click="selectFrequency('one_time')"
                                class="min-h-12 rounded-md border px-3 text-base font-medium transition {{ $frequency === 'one_time' ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-slate-200 text-slate-700' }}"
                                >
                                    One-time
                                </button>

                                @if ($allowMonthly)
                                    <button
                                        type="button"
                                        wire:click="selectFrequency('monthly')"
                                        class="min-h-12 rounded-md border px-3 text-base font-semibold transition {{ $frequency === 'monthly' ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-slate-200 text-slate-700' }}"
                                    >
                                        <span style="color: {{ $iconColor }};">&hearts;</span>
                                        Monthly
                                    </button>
                                @endif
                            </div>

                            <h2 class="pt-5 text-center text-lg font-medium">{{ $title }}</h2>

                            @if ($this->config('show_suggested', true))
                                <div class="grid grid-cols-3 gap-2">
                                    @foreach ($suggestedAmounts as $suggestedAmount)
                                        <button
                                            type="button"
                                            wire:key="amount-{{ $suggestedAmount }}"
                                            wire:click="selectAmount({{ $suggestedAmount }})"
                                            class="min-h-14 rounded-md border px-2 text-base font-medium transition {{ (float) $amount === (float) $suggestedAmount ? 'border-blue-500 bg-blue-50 text-blue-600' : 'border-slate-200 text-slate-700' }}"
                                        >
                                            RM {{ number_format($suggestedAmount) }}
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            @if ($this->config('show_amount_input', true))
                                <label class="block">
                                    <span class="sr-only">Donation amount</span>
                                    <div class="flex min-h-20 items-center rounded-md border border-slate-300 px-4 focus-within:border-blue-500">
                                        <span class="text-2xl font-medium">RM</span>
                                        <input
                                            wire:model.live.debounce.300ms="amount"
                                            type="number"
                                            min="1"
                                            step="1"
                                            class="min-w-0 flex-1 border-0 bg-transparent px-2 text-5xl/none font-semibold text-blue-600 outline-none"
                                        />
                                        <span class="text-lg font-medium">MYR</span>
                                    </div>
                                    @error('amount')
                                        <span class="mt-1 block text-base text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>
                            @endif

                            <div class="grid gap-3">
                                <label class="block">
                                    <span class="mb-1 block text-base font-medium">Name</span>
                                    <input wire:model="name" type="text" autocomplete="name" class="min-h-12 w-full rounded-md border border-slate-200 px-3 text-base outline-none focus:border-blue-500" />
                                    @error('name')
                                        <span class="mt-1 block text-base text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-base font-medium">Email</span>
                                    <input wire:model="email" type="email" autocomplete="email" class="min-h-12 w-full rounded-md border border-slate-200 px-3 text-base outline-none focus:border-blue-500" />
                                    @error('email')
                                        <span class="mt-1 block text-base text-red-600">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="block">
                                    <span class="mb-1 block text-base font-medium">Phone <span class="text-slate-400">(optional)</span></span>
                                    <input wire:model="phone" type="tel" autocomplete="tel" class="min-h-12 w-full rounded-md border border-slate-200 px-3 text-base outline-none focus:border-blue-500" />
                                </label>
                            </div>

                            @if ($showDedication)
                                <label class="flex items-center gap-2 text-base">
                                    <input wire:model="dedicate" type="checkbox" class="size-5 rounded border-slate-300" />
                                    Dedicate this donation
                                </label>
                            @endif

                            @if ($showComment)
                                <label class="block">
                                    <span class="mb-1 block text-base underline">Add comment</span>
                                    <textarea wire:model="comment" rows="2" class="w-full rounded-md border border-slate-200 px-3 py-2 text-base outline-none focus:border-blue-500"></textarea>
                                </label>
                            @endif

                            <div wire:ignore>
                                <label class="mb-1 block text-base font-medium">Card details</label>
                                <div id="card-element" class="min-h-12 rounded-md border border-slate-200 px-3 py-3"></div>
                                <div x-show="cardError" x-cloak class="mt-1 text-sm text-red-600" x-text="cardError"></div>
                            </div>

                            <button
                                type="submit"
                                class="min-h-16 w-full rounded-md bg-blue-600 px-4 text-lg font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-70"
                                x-bind:disabled="processing"
                            >
                                <span x-show="!processing">{{ $submitText }}</span>
                                <span x-show="processing" x-cloak>Processing...</span>
                            </button>
                        </form>
                    </div>

                    <div x-show="processing" x-cloak class="py-10 text-center">
                        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-blue-50 text-2xl text-blue-600">
                            <svg class="size-6 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                        </div>
                        <h2 class="text-xl font-semibold">Processing payment...</h2>
                        <p class="mt-2 text-sm text-slate-500">Please wait while we process your donation.</p>
                    </div>

                    <div x-show="success" x-cloak class="py-10 text-center">
                        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-emerald-50 text-2xl text-emerald-600">
                            &check;
                        </div>
                        <h2 class="text-xl font-semibold">Thank you!</h2>
                        <p class="mt-2 text-sm text-slate-500">{{ $this->config('success_message', 'Thank you for your donation!') }}</p>
                    </div>

                    <div x-show="error" x-cloak class="py-10 text-center">
                        <div class="mx-auto mb-4 flex size-14 items-center justify-center rounded-full bg-red-50 text-2xl text-red-600">
                            &times;
                        </div>
                        <h2 class="text-xl font-semibold">Payment failed</h2>
                        <p class="mt-2 text-sm text-slate-500" x-text="errorMessage"></p>
                        <button type="button" @click="window.location.reload()" class="mt-4 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
                            Try again
                        </button>
                    </div>
                </div>
            </div>

@if ($isPopup)
    </div>
@else
            <div class="mx-auto mt-4 size-7 rounded-full bg-slate-300"></div>
        </section>
    </main>
</div>
@endif

@script
<script>
    Alpine.data('donationForm', () => ({
        processing: false,
        success: false,
        error: false,
        errorMessage: '',
        cardError: '',
        cardElement: null,
        stripe: null,

        async init() {
            this.stripe = Stripe(window.stripePublishableKey);
            const elements = this.stripe.elements({ locale: 'ms' });
            this.cardElement = elements.create('card', {
                hidePostalCode: true,
                style: {
                    base: { fontSize: '16px', color: '#212830', '::placeholder': { color: '#94a3b8' } },
                },
            });
            this.cardElement.mount('#card-element');
            this.cardElement.on('change', (event) => {
                this.cardError = event.error ? event.error.message : '';
            });
        },

        async handleSubmit() {
            this.processing = true;

            let clientSecret;

            try {
                clientSecret = await $wire.submit();
            } catch (e) {
                this.processing = false;
                return;
            }

            if (!clientSecret) {
                this.processing = false;
                this.error = true;
                this.errorMessage = 'Payment could not be processed. Please try again.';
                return;
            }

            const { paymentIntent, error: confirmError } = await this.stripe.confirmCardPayment(clientSecret, {
                payment_method: {
                    card: this.cardElement,
                    billing_details: {
                        name: $wire.name,
                        email: $wire.email,
                        phone: $wire.phone,
                    },
                },
            });

            if (confirmError) {
                this.processing = false;
                this.error = true;
                this.errorMessage = confirmError.message;
                return;
            }

            if (paymentIntent.status === 'succeeded') {
                this.processing = false;
                this.success = true;
            }
        },
    }));
</script>
@endscript
