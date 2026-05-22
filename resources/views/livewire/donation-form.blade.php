@php
    $campaign = $element->campaign;
    $organization = $campaign->organization;
    $oneTimeAmounts = $this->suggestedAmounts('one_time');
    $monthlyAmounts = $this->suggestedAmounts('monthly');
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
    $isPopup = $this->isPopup;
    $isEmbed = $this->isEmbed;
    $isCompact = $isPopup || $isEmbed;
    $usesSecureDonationTemplate = $this->config('template', 'secure-donation') === 'secure-donation';
    $usesSecureDonationShell = $usesSecureDonationTemplate && ! $isEmbed;
    $campaignImageUrl = filled($campaign->image_path) ? route('donations.campaign-image', $element) : null;
    $introTitle = filled($campaign->headline) ? $campaign->headline : $campaign->title;
    $introText = filled($campaign->short_summary) ? $campaign->short_summary : strip_tags($campaign->description ?? '');
@endphp

<div>
    @if ($usesSecureDonationShell)
        @if ($isPopup)
            <div class="bg-white">
        @else
            <div class="min-h-screen bg-[#eef1f6] px-4 py-8 sm:px-6 lg:px-8">
                <main class="mx-auto w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-2xl">
        @endif
            @if ($campaignImageUrl)
                <div class="p-2.5 pb-0 sm:p-3 sm:pb-0">
                    <img
                        src="{{ $campaignImageUrl }}"
                        alt="{{ $campaign->title }}"
                        class="h-56 w-full rounded-2xl object-cover sm:h-64"
                    />
                </div>
            @endif

            <div class="px-6 py-6">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex size-10 items-center justify-center rounded-xl bg-teal-700 text-base font-bold text-white shadow-sm">
                        {{ str($organization->name)->substr(0, 1)->upper() }}
                    </div>
                    <p class="min-w-0 truncate text-xs font-bold uppercase tracking-[0.18em] text-slate-400">{{ $organization->name }}</p>
                </div>

                <h1 class="text-2xl font-bold tracking-normal text-slate-950">{{ $introTitle }}</h1>

                @if (filled($introText))
                    <p class="mt-3 text-base/7 text-slate-600">{{ $introText }}</p>
                @endif

                @if ($campaign->has_target)
                    @php
                        $targetAmount = max((float) $campaign->target_amount, 1);
                        $collectedAmount = (float) $campaign->collected_amount;
                        $progress = min(100, round(($collectedAmount / $targetAmount) * 100));
                    @endphp

                    <div class="mt-5">
                        <div class="mb-1.5 flex items-end justify-between gap-4 text-xs">
                            <span class="font-semibold text-slate-950">RM {{ number_format($collectedAmount, 2) }} raised</span>
                            <span class="text-slate-500">Goal RM {{ number_format($targetAmount, 2) }}</span>
                        </div>
                        <div class="h-1.5 rounded-full bg-slate-200">
                            <div class="h-1.5 rounded-full bg-teal-600" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>
                @endif
            </div>

            <section class="border-t border-slate-200 px-6 py-6">
                <div class="mb-5 flex items-center gap-3">
                    <span class="flex size-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-emerald-100">
                        <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.75 5.25 6v5.25c0 4.2 2.86 8.1 6.75 9 3.89-.9 6.75-4.8 6.75-9V6L12 3.75Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 12.25 1.5 1.5 3-3.5" />
                        </svg>
                    </span>
                    <h2 class="text-xl font-bold tracking-normal text-slate-950">Secure donation</h2>
                </div>
    @elseif ($isEmbed)
        <div class="px-4 py-5 sm:px-5">
            <div class="mb-3 flex items-center gap-2.5">
                <div class="flex size-8 items-center justify-center rounded-md bg-teal-700 text-sm font-bold text-white">
                    {{ str($organization->name)->substr(0, 1)->upper() }}
                </div>
                <div class="min-w-0">
                    <p class="truncate text-xs font-medium text-slate-500">{{ $organization->name }}</p>
                    <h1 class="text-sm font-semibold text-slate-950">{{ $campaign->title }}</h1>
                </div>
            </div>

            @if ($campaign->has_target)
                @php
                    $targetAmount = max((float) $campaign->target_amount, 1);
                    $collectedAmount = (float) $campaign->collected_amount;
                    $progress = min(100, round(($collectedAmount / $targetAmount) * 100));
                @endphp

                <div class="mb-3">
                    <div class="mb-1 flex items-end justify-between gap-4 text-xs">
                        <span class="font-semibold text-slate-950">RM {{ number_format($collectedAmount, 2) }} raised</span>
                        <span class="text-slate-500">Goal RM {{ number_format($targetAmount, 2) }}</span>
                    </div>
                    <div class="h-1.5 rounded-full bg-slate-200">
                        <div class="h-1.5 rounded-full bg-teal-700" style="width: {{ $progress }}%"></div>
                    </div>
                </div>
            @endif
    @elseif (! $isCompact)
        <div class="min-h-screen bg-[#eef1f6] px-4 py-8 sm:px-6 lg:px-8">
            <main class="mx-auto grid max-w-6xl items-center gap-8 lg:grid-cols-[minmax(0,1fr)_440px]">
                <section class="rounded-xl border border-white/70 bg-white/80 p-6 shadow-sm backdrop-blur sm:p-8">
                    <div class="mb-6 flex items-center gap-4">
                        <div class="flex size-14 items-center justify-center rounded-xl bg-teal-700 text-xl font-bold text-white shadow-sm">
                            {{ str($organization->name)->substr(0, 1)->upper() }}
                        </div>
                        <div>
                            <p class="text-sm font-medium text-slate-500">{{ $organization->name }}</p>
                            <h1 class="text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">{{ $campaign->title }}</h1>
                        </div>
                    </div>

                    @if ($campaign->description)
                        <p class="max-w-2xl text-base/7 text-slate-600">{{ $campaign->description }}</p>
                    @endif

                    @if ($campaign->has_target)
                        @php
                            $targetAmount = max((float) $campaign->target_amount, 1);
                            $collectedAmount = (float) $campaign->collected_amount;
                            $progress = min(100, round(($collectedAmount / $targetAmount) * 100));
                        @endphp

                        <div class="mt-6 max-w-2xl">
                            <div class="mb-2 flex items-end justify-between gap-4 text-sm">
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
                    class="{{ $showShadow && ! $isPopup ? 'shadow-xl' : '' }} {{ $isPopup ? '' : ($isEmbed ? 'p-5 sm:p-6' : 'p-6') }}"
                    @if (! $isPopup)
                        style="background-color: {{ $backgroundColor }}; color: {{ $textColor }}; border: {{ $borderSize }}px solid {{ $borderColor }}; border-radius: {{ $isCompact ? $borderRadius : $borderRadius + 10 }}px;"
                    @endif
                >
                    <div x-data="donationForm(@js($frequency), @js($amount))">
                        <div x-show="!processing && !success && !error">
                            <form class="{{ $usesSecureDonationShell ? 'space-y-3.5' : 'space-y-4' }}" @submit.prevent="handleSubmit">
                                <div class="grid grid-cols-2 gap-2">
                                    <button
                                        type="button"
                                        x-on:click="frequency = 'one_time'"
                                        x-bind:class="frequency === 'one_time' ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                        class="min-h-10 rounded-lg border bg-white px-3 text-sm font-semibold transition"
                                    >
                                        Give once
                                    </button>

                                    @if ($allowMonthly)
                                        <button
                                            type="button"
                                            x-on:click="frequency = 'monthly'"
                                            x-bind:class="frequency === 'monthly' ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-slate-200 text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                                            class="min-h-10 rounded-lg border bg-white px-3 text-sm font-semibold transition"
                                        >
                                            <span style="color: {{ $iconColor }};">&hearts;</span>
                                            Monthly
                                        </button>
                                    @endif
                                </div>

                                @unless ($usesSecureDonationShell)
                                    <h2 class="pt-3 text-center text-sm font-medium text-slate-800">{{ $title }}</h2>
                                @endunless

                                @if ($this->config('show_suggested', true))
                                    <div x-show="frequency === 'one_time'" class="grid grid-cols-3 gap-2">
                                        @foreach ($oneTimeAmounts as $amount)
                                            <button
                                                type="button"
                                                wire:key="one_time_{{ $amount }}"
                                                x-on:click="amount = {{ $amount }}"
                                                x-bind:class="Number(amount) === {{ $amount }} ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
                                                class="min-h-12 rounded-lg border bg-white px-2 text-sm font-semibold transition"
                                            >
                                                RM {{ number_format($amount) }}
                                            </button>
                                        @endforeach
                                    </div>

                                    <div x-show="frequency === 'monthly'" class="grid grid-cols-3 gap-2">
                                        @foreach ($monthlyAmounts as $amount)
                                            <button
                                                type="button"
                                                wire:key="monthly_{{ $amount }}"
                                                x-on:click="amount = {{ $amount }}"
                                                x-bind:class="Number(amount) === {{ $amount }} ? 'border-teal-600 bg-teal-50 text-teal-700 shadow-sm' : 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
                                                class="min-h-12 rounded-lg border bg-white px-2 text-sm font-semibold transition"
                                            >
                                                RM {{ number_format($amount) }}
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                                @if ($this->config('show_amount_input', true))
                                    <label class="block">
                                        <span class="sr-only">Donation amount</span>
                                        <div class="flex min-h-14 items-center rounded-xl border border-slate-300 bg-white px-4 transition focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-600/20">
                                            <span class="{{ $usesSecureDonationShell ? 'text-2xl' : 'text-base' }} font-semibold text-slate-700">RM</span>
                                            <input
                                                x-model="amount"
                                                type="number"
                                                min="1"
                                                step="1"
                                                class="min-w-0 flex-1 border-0 bg-transparent px-2 text-3xl/none font-bold text-slate-950 outline-none placeholder:text-slate-300 sm:px-3 [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                                            />
                                            <span class="text-sm font-medium text-slate-500">MYR</span>
                                        </div>
                                        @error('amount')
                                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </label>
                                @endif

                                <div class="space-y-3 pt-2">
                                    <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">Your details</p>

                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-slate-700">Name</span>
                                        <input wire:model="name" type="text" autocomplete="name" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20" placeholder="Your full name" />
                                        @error('name')
                                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-slate-700">Email</span>
                                        <input wire:model="email" type="email" autocomplete="email" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20" placeholder="you@example.com" />
                                        @error('email')
                                            <span class="mt-1 block text-sm text-red-600">{{ $message }}</span>
                                        @enderror
                                    </label>

                                    <label class="block">
                                        <span class="mb-1 block text-sm font-medium text-slate-700">Phone <span class="text-slate-400 font-normal">(optional)</span></span>
                                        <input wire:model="phone" type="tel" autocomplete="tel" class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/20" placeholder="012-345 6789" />
                                    </label>
                                </div>

                                @if ($showDedication)
                                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-700">
                                        <input wire:model="dedicate" type="checkbox" class="size-4 rounded border-slate-300 text-teal-600 focus:ring-teal-600" />
                                        Dedicate this donation
                                    </label>
                                @endif

                                @if ($showComment)
                                    <label class="block">
                                        <span class="mb-0.5 block text-sm font-medium text-slate-700">Comment <span class="text-slate-400 font-normal">(optional)</span></span>
                                        <textarea wire:model="comment" rows="2" class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none transition focus:border-teal-600 focus:ring-2 focus:ring-teal-600/10" placeholder="Leave a message..."></textarea>
                                    </label>
                                @endif

                                <div wire:ignore>
                                    <label class="mb-0.5 block text-sm font-medium text-slate-700">Card details</label>
                                    <div id="card-element" class="min-h-10 rounded-lg border border-slate-200 px-3 py-2.5 transition focus-within:border-teal-600 focus-within:ring-2 focus-within:ring-teal-600/10"></div>
                                    <div x-show="cardError" x-cloak class="mt-1 text-sm text-red-600" x-text="cardError"></div>
                                </div>

                                <button
                                    type="submit"
                                    class="min-h-12 w-full rounded-lg bg-teal-600 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-teal-700 active:scale-[0.98] disabled:opacity-60"
                                    x-bind:disabled="processing"
                                >
                                    @if ($usesSecureDonationShell && in_array($submitText, ['Donate and Support', 'Donate Now'], true))
                                        <span x-show="!processing && frequency === 'monthly'">Donate monthly</span>
                                        <span x-show="!processing && frequency !== 'monthly'">Donate once</span>
                                    @else
                                        <span x-show="!processing">{{ $submitText }}</span>
                                    @endif
                                    <span x-show="processing" x-cloak>Processing...</span>
                                </button>
                            </form>
                        </div>

                        <div x-show="processing" x-cloak class="py-8 text-center">
                            <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-teal-50">
                                <svg class="size-5 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            </div>
                            <h2 class="text-base font-semibold text-slate-900">Processing payment...</h2>
                            <p class="mt-1 text-sm text-slate-500">Please wait while we process your donation.</p>
                        </div>

                        <div x-show="success" x-cloak class="py-8 text-center">
                            <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-emerald-50">
                                <svg class="size-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            </div>
                            <h2 class="text-base font-semibold text-slate-900">Thank you!</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $this->config('success_message', 'Thank you for your donation!') }}</p>
                        </div>

                        <div x-show="error" x-cloak class="py-8 text-center">
                            <div class="mx-auto mb-3 flex size-12 items-center justify-center rounded-full bg-red-50">
                                <svg class="size-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </div>
                            <h2 class="text-base font-semibold text-slate-900">Payment failed</h2>
                            <p class="mt-1 text-sm text-slate-500" x-text="errorMessage"></p>
                            <button type="button" @click="window.location.reload()" class="mt-4 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700">
                                Try again
                            </button>
                        </div>
                    </div>
                </div>

    @if ($usesSecureDonationShell)
            </section>
        @if ($isPopup)
            </div>
        @else
                </main>
            </div>
        @endif
    @elseif ($isEmbed)
        </div>
    @else
                    <div class="mx-auto mt-4 size-7 rounded-full bg-slate-300"></div>
                </section>
            </main>
        </div>
    @endif
</div>

@script
<script>
    Alpine.data('donationForm', (initialFrequency, initialAmount) => {
        let stripe = null;
        let cardElement = null;

        return {
            frequency: initialFrequency,
            amount: initialAmount,
            processing: false,
            success: false,
            error: false,
            errorMessage: '',
            cardError: '',

            async init() {
                stripe = Stripe(window.stripePublishableKey);
                const elements = stripe.elements({ locale: 'ms' });
                cardElement = elements.create('card', {
                    hidePostalCode: true,
                    style: {
                        base: { fontSize: '16px', color: '#212830', '::placeholder': { color: '#94a3b8' } },
                    },
                });
                cardElement.mount('#card-element');
                cardElement.on('change', (event) => {
                    this.cardError = event.error ? event.error.message : '';
                });
            },

            async handleSubmit() {
                this.processing = true;
                this.cardError = '';

                const { paymentMethod, error: pmError } = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                    billing_details: {
                        name: $wire.name,
                        email: $wire.email,
                        phone: $wire.phone,
                    },
                });

                if (pmError) {
                    this.processing = false;
                    this.error = true;
                    this.errorMessage = pmError.message;
                    return;
                }

                $wire.$set('frequency', this.frequency, false);
                $wire.$set('amount', this.amount, false);

                let clientSecret;

                try {
                    clientSecret = await $wire.submit();
                } catch (e) {
                    this.processing = false;
                    return;
                }

                if (!clientSecret) {
                    this.processing = false;
                    return;
                }

                const { paymentIntent, error: confirmError } = await stripe.confirmCardPayment(clientSecret, {
                    payment_method: paymentMethod.id,
                });

                if (confirmError) {
                    this.processing = false;
                    this.error = true;
                    this.errorMessage = confirmError.message;
                    return;
                }

                if (paymentIntent.status === 'succeeded') {
                    await $wire.confirmPayment(paymentIntent.id);
                    this.processing = false;
                    this.success = true;
                }
            },
        };
    });
</script>
@endscript
