@extends('donor.layout')

@section('title', 'Subscriptions')

@section('content')
<div class="donor-wrap" x-data="{
    loaded: false,
    paymentModal: null,
    pauseConfirmId: null,
    cancelConfirmId: null,
    paymentClientSecret: null,
    paymentStripeAccount: null,
    paymentMounting: false,
    paymentLoading: false,
    paymentSuccess: false,
    paymentError: null,
    async openPayment(subscriptionId) {
        this.paymentModal = subscriptionId;
        this.paymentSuccess = false;
        this.paymentLoading = false;
        this.paymentMounting = true;
        this.paymentError = null;
        this.paymentClientSecret = null;
        this.paymentStripeAccount = null;

        const res = await fetch('{{ route('donorportal.subscriptions.payment-method.client-secret', ['organization' => $organization, 'subscription' => '__id__']) }}'.replace('__id__', subscriptionId));
        const data = await res.json();

        if (data.error) {
            this.paymentError = data.error;
            this.paymentMounting = false;
            this.paymentModal = null;
            return;
        }

        if (this.paymentModal === null) {
            this.paymentMounting = false;
            return;
        }

        this.paymentClientSecret = data.client_secret;
        this.paymentStripeAccount = data.stripe_account_id;

        if (!window.Stripe) {
            this.paymentError = 'Stripe failed to load. Please try again.';
            this.paymentMounting = false;
            return;
        }

        const stripeConfig = {};
        if (data.stripe_account_id) {
            stripeConfig.stripeAccount = data.stripe_account_id;
        }

        const stripe = Stripe('{{ config('services.stripe.key') }}', stripeConfig);
        const elements = stripe.elements({
            clientSecret: data.client_secret,
            appearance: { theme: 'stripe' },
        });

        const card = elements.create('payment', {
            paymentMethodTypes: ['card'],
        });
        card.mount('#stripe-card-element');
        this.paymentMounting = false;

        card.on('change', (event) => {
            const el = document.getElementById('stripe-card-errors');
            if (el) el.textContent = event.error ? event.error.message : '';
        });

        window.__stripeInstance = stripe;
        window.__stripeElements = elements;
    },
    async submitPayment(subscriptionId) {
        this.paymentLoading = true;

        const { setupIntent, error } = await window.__stripeInstance.confirmSetup({
            elements: window.__stripeElements,
            redirect: 'if_required',
        });

        if (error) {
            document.getElementById('stripe-card-errors').textContent = error.message;
            this.paymentLoading = false;
            return;
        }

        const res = await fetch('{{ route('donorportal.subscriptions.payment-method.update', ['organization' => $organization, 'subscription' => '__id__']) }}'.replace('__id__', subscriptionId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ payment_method_id: setupIntent.payment_method }),
        });

        const data = await res.json();

        if (data.error) {
            document.getElementById('stripe-card-errors').textContent = data.error;
            this.paymentLoading = false;
            return;
        }

        this.paymentSuccess = true;
        this.paymentLoading = false;
        setTimeout(() => { location.reload(); }, 1000);
    }
}" x-init="$nextTick(() => setTimeout(() => loaded = true, 400))">
    <div class="donor-skeleton" x-show="!loaded" x-transition.opacity.duration.250ms x-cloak aria-hidden="true">
        <div class="mb-8">
            <div class="h-8 w-48 animate-pulse rounded-lg bg-slate-200"></div>
            <div class="mt-1 h-4 w-64 animate-pulse rounded bg-slate-100"></div>
        </div>
        <div class="space-y-3">
            <div class="h-32 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
            <div class="h-32 animate-pulse rounded-xl bg-slate-100 donor-card-skeleton"></div>
        </div>
    </div>
    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-black tracking-tight text-slate-900 [letter-spacing:-0.02em]">Subscriptions</h1>
            <p class="mt-0.5 text-xs text-slate-500">Manage your recurring donations.</p>
        </div>

        @if (session('error'))
            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div x-show="paymentError" x-cloak
             class="mb-6 rounded-xl border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700"
             x-text="paymentError"></div>

        <div class="space-y-3">
            @forelse ($subscriptions as $subscription)
                <div class="rounded-xl bg-white p-4 transition {{ $subscription->status === \App\Enums\SubscriptionStatus::Cancelled ? 'opacity-60' : 'hover:shadow-md' }}"
                     style="border:1.5px solid #e2e8f0;">
                    <div class="flex flex-col sm:flex-row items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-bold text-slate-900">{{ $subscription->campaign->title }}</p>
                            <p class="mt-0.5 text-xs text-slate-500">{{ $subscription->campaign->organization->name }}</p>
                        </div>
                        <div class="flex-shrink-0 sm:text-right text-left">
                            <p class="text-base font-black text-slate-900">
                                {{ $subscription->currency_symbol }} {{ number_format($subscription->amount, 2) }}<span class="text-xs font-normal text-slate-400">/{{ $subscription->interval->value }}</span>
                            </p>
                            @if ($subscription->current_period_end)
                                <p class="mt-0.5 text-xs text-slate-400">
                                    Next: {{ $subscription->current_period_end->format('d M Y') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-3 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                        @php
                            $statusClass = match ($subscription->status) {
                                \App\Enums\SubscriptionStatus::Active            => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                \App\Enums\SubscriptionStatus::Cancelled         => 'bg-slate-50 text-slate-600 border-slate-200',
                                \App\Enums\SubscriptionStatus::PastDue           => 'bg-red-50 text-red-600 border-red-200',
                                \App\Enums\SubscriptionStatus::Paused            => 'bg-amber-50 text-amber-700 border-amber-200',
                                \App\Enums\SubscriptionStatus::Incomplete        => 'bg-amber-50 text-amber-700 border-amber-200',
                                \App\Enums\SubscriptionStatus::IncompleteExpired => 'bg-slate-50 text-slate-500 border-slate-200',
                                \App\Enums\SubscriptionStatus::Completed        => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                            };
                            $statusPrefix = $subscription->status === \App\Enums\SubscriptionStatus::Active ? '● ' : '';
                        @endphp
                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold {{ $statusClass }}">
                            {{ $statusPrefix }}{{ str($subscription->status->value)->headline() }}
                        </span>

                        @if ($subscription->status === \App\Enums\SubscriptionStatus::Active)
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="{{ route('donorportal.subscriptions.increase', ['organization' => $organization, 'subscription' => $subscription]) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                                    <x-heroicon name="plus" class="h-3.5 w-3.5" />
                                    Change Amount
                                </a>
                                <button @click="openPayment('{{ $subscription->public_id }}')"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                                    <x-heroicon name="credit-card" class="h-3.5 w-3.5" />
                                    Update Card Details
                                </button>
                                <button type="button"
                                        @click="pauseConfirmId = '{{ $subscription->public_id }}'"
                                        class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-600 transition hover:bg-amber-100">
                                    <x-heroicon name="pause" class="h-3.5 w-3.5" />
                                    Pause
                                </button>
                                <a href="{{ route('donorportal.donations', ['organization' => $organization, 'subscription' => $subscription->public_id]) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                                    <x-heroicon name="list-bullet" class="h-3.5 w-3.5" />
                                    History
                                </a>
                                <button type="button"
                                        @click="cancelConfirmId = '{{ $subscription->public_id }}'"
                                        class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-bold text-red-600 transition hover:bg-red-100">
                                    <x-heroicon name="x-mark" class="h-3.5 w-3.5" />
                                    Cancel
                                </button>
                            </div>
                        @elseif ($subscription->status === \App\Enums\SubscriptionStatus::Paused)
                            <div class="flex flex-wrap items-center gap-2">
                                <form action="{{ route('donorportal.subscriptions.resume', ['organization' => $organization, 'subscription' => $subscription]) }}"
                                      method="POST"
                                      onsubmit="return confirm('Resume this subscription?')"
                                      class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-600 transition hover:bg-emerald-100">
                                        <x-heroicon name="play" class="h-3.5 w-3.5" />
                                        Resume
                                    </button>
                                </form>
                                <a href="{{ route('donorportal.donations', ['organization' => $organization, 'subscription' => $subscription->public_id]) }}"
                                   class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                                    <x-heroicon name="list-bullet" class="h-3.5 w-3.5" />
                                    History
                                </a>
                            </div>
                        @else
                            <a href="{{ route('donorportal.donations', ['organization' => $organization, 'subscription' => $subscription->public_id]) }}"
                               class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                                <x-heroicon name="list-bullet" class="h-3.5 w-3.5" />
                                History
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="rounded-xl bg-white px-8 py-16 text-center donor-card">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                        <x-heroicon name="list-bullet" class="h-7 w-7 text-slate-400" />
                    </div>
                    <p class="text-sm font-bold text-slate-700">No subscriptions yet</p>
                    <p class="mt-1.5 text-xs text-slate-500">Set up a recurring donation to start a subscription.</p>
                </div>
            @endforelse
        </div>

        @if ($subscriptions->hasPages())
            <div class="mt-8">{{ $subscriptions->links() }}</div>
        @endif
    </div>

    {{-- Pause Confirmation Modal --}}
    <div x-show="pauseConfirmId !== null"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @click.self="pauseConfirmId = null"
         x-transition.opacity>
        <div class="w-full max-w-sm mx-auto max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl" @click.stop>
            <h3 class="text-base font-black text-slate-900">Pause Subscription?</h3>
            <p class="mt-1 text-xs text-slate-500">No further payments will be collected until you resume.</p>

            <form method="POST"
                  x-bind:action="'{{ route('donorportal.subscriptions.pause', ['organization' => $organization, 'subscription' => '__id__']) }}'.replace('__id__', pauseConfirmId)"
                  class="mt-6 flex items-center justify-end gap-2">
                @csrf
                <button type="button"
                        @click="pauseConfirmId = null"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                    Keep Active
                </button>
                <button type="submit"
                        class="rounded-lg border border-transparent bg-amber-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-amber-500">
                    Yes, Pause
                </button>
            </form>
        </div>
    </div>

    {{-- Cancel Confirmation Modal --}}
    <div x-show="cancelConfirmId !== null"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @click.self="cancelConfirmId = null"
         x-transition.opacity>
        <div class="w-full max-w-sm mx-auto max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl" @click.stop>
            <h3 class="text-base font-black text-slate-900">Cancel Subscription?</h3>
            <p class="mt-1 text-xs text-slate-500">Your subscription will stop at the end of the current billing period. No further charges after that.</p>

            <form method="POST"
                  x-bind:action="'{{ route('donorportal.subscriptions.cancel', ['organization' => $organization, 'subscription' => '__id__']) }}'.replace('__id__', cancelConfirmId)"
                  class="mt-6 flex items-center justify-end gap-2">
                @csrf
                <button type="button"
                        @click="cancelConfirmId = null"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                    Keep Active
                </button>
                <button type="submit"
                        class="rounded-lg border border-transparent bg-red-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-red-500">
                    Yes, Cancel
                </button>
            </form>
        </div>
    </div>

    {{-- Payment Method Modal --}}
    <div x-show="paymentModal !== null"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
         @click.self="paymentModal = null; paymentMounting = false"
         x-transition.opacity>
        <div class="w-full max-w-md mx-auto max-h-[90vh] overflow-y-auto rounded-2xl bg-white p-6 shadow-xl" @click.stop>
            <h3 class="text-base font-black text-slate-900">Update Card Details</h3>
            <div class="max-h-[28rem] overflow-y-auto">
                <div class="mt-4">
                    <div x-show="paymentMounting" class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="space-y-2">
                            <div class="h-3 w-1/3 animate-pulse rounded bg-slate-200"></div>
                            <div class="h-9 animate-pulse rounded bg-slate-200"></div>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div class="h-9 animate-pulse rounded bg-slate-200"></div>
                                <div class="h-9 animate-pulse rounded bg-slate-200"></div>
                            </div>
                        </div>
                    </div>
                    <div id="stripe-card-element" x-show="!paymentMounting" class="rounded-lg border border-gray-300 p-3"></div>
                    <div id="stripe-card-errors" class="mt-2 text-xs text-red-500"></div>
                </div>
            </div>
            <div x-show="paymentLoading" class="mt-2 text-xs text-slate-500">Processing...</div>
            <div x-show="paymentSuccess" class="mt-2 rounded-lg bg-emerald-50 p-3 text-xs font-bold text-emerald-700">
                Card updated successfully!
            </div>
            <div class="mt-4 flex items-center justify-end gap-2">
                <button @click="paymentModal = null; paymentMounting = false"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-600 transition hover:bg-slate-50">
                    Close
                </button>
                <button @click="submitPayment(paymentModal)"
                        x-show="!paymentSuccess"
                        x-text="paymentLoading ? 'Saving...' : 'Save Card'"
                        :disabled="paymentLoading || paymentClientSecret === null"
                        class="rounded-lg border border-transparent bg-slate-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-slate-700 disabled:opacity-50">
                    Save Card
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://js.stripe.com/v3/"></script>
@endpush
