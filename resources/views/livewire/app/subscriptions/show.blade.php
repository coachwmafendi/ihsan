{{-- resources/views/livewire/app/subscriptions/show.blade.php --}}
<div
    class="space-y-6"
    x-data="{
        copied: false,
        activeSection: 'section-subscription',
        init() {
            const sections = document.querySelectorAll('[data-section]');
            if (sections.length === 0) {
                return;
            }

            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            this.activeSection = entry.target.dataset.section;
                        }
                    });
                },
                { rootMargin: '-15% 0px -55% 0px', threshold: 0 },
            );

            sections.forEach((section) => observer.observe(section));
        },
        scrollToSection(id) {
            const element = document.getElementById(id);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },
    }"
>
    {{-- Page Header --}}
    <div>
        <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $this->formattedAmount() }} / {{ strtolower($this->frequencyLabel()) }}</h1>
        <p class="mt-1 flex items-center gap-2 text-sm text-slate-500">
            <span>ID {{ $subscription->public_id }}</span>
            <button
                x-on:click="navigator.clipboard.writeText('{{ $subscription->public_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                title="Copy subscription ID"
            >
                <x-heroicon-o-clipboard-document class="size-3.5" />
            </button>
            <span x-show="copied" x-transition class="text-xs text-emerald-600">Copied!</span>
            <span>· <x-ui.badge status="{{ $subscription->status->value }}" size="sm">{{ $subscription->status->getLabel() }}</x-ui.badge></span>
        </p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_18rem] xl:grid-cols-[1fr_20rem]">
        {{-- Left Column --}}
        <div class="space-y-6">
            {{-- Subscription --}}
            <section id="section-subscription" data-section="section-subscription">
                <x-ui.card title="Subscription" icon="heroicon-o-arrow-path">
                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Amount</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $this->formattedAmount() }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Subscription ID</dt>
                            <dd class="flex items-center gap-2 text-sm font-medium text-slate-900">
                                {{ $subscription->public_id }}
                                <button
                                    x-on:click="navigator.clipboard.writeText('{{ $subscription->public_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                    title="Copy subscription ID"
                                >
                                    <x-heroicon-o-clipboard-document class="size-3.5" />
                                </button>
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Supporter</dt>
                            <dd class="text-sm font-medium">
                                @if ($subscription->donor)
                                    <a href="{{ route('app.supporters.show', $subscription->donor) }}" wire:navigate class="text-blue-600 hover:text-blue-700">
                                        {{ $subscription->donor->name }}
                                    </a>
                                @else
                                    <span class="text-slate-900">Unknown</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Campaign</dt>
                            <dd class="text-sm font-medium">
                                @if ($subscription->campaign)
                                    <a href="{{ route('app.campaigns.edit', $subscription->campaign) }}" wire:navigate class="text-blue-600 hover:text-blue-700">
                                        {{ $subscription->campaign->title }}
                                    </a>
                                @else
                                    <span class="text-slate-500">No campaign linked</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Create date</dt>
                            <dd class="text-sm text-slate-900">{{ $subscription->created_at->format('M d, Y, H:i') }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Frequency</dt>
                            <dd class="text-sm text-slate-900">{{ $this->frequencyLabel() }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Status</dt>
                            <dd><x-ui.badge status="{{ $subscription->status->value }}" size="sm">{{ $subscription->status->getLabel() }}</x-ui.badge></dd>
                        </div>
                    </dl>
                </x-ui.card>
            </section>

            {{-- Billing --}}
            <section id="section-billing" data-section="section-billing">
                <x-ui.card title="Billing" icon="heroicon-o-credit-card">
                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Total paid</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $this->totalPaidAmount }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Installments paid</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $this->totalPaymentsCount }}</dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Fee covered</dt>
                            <dd>
                                @if ($subscription->cover_fee)
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        <x-heroicon-o-check-circle class="size-4" />
                                        Covered
                                    </span>
                                @else
                                    <span class="text-sm text-slate-900">Not covered</span>
                                @endif
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Current period</dt>
                            <dd class="text-sm text-slate-900">
                                {{ $subscription->current_period_start?->format('M d, Y') ?? '—' }} – {{ $subscription->current_period_end?->format('M d, Y') ?? '—' }}
                            </dd>
                        </div>
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Next billing</dt>
                            <dd class="text-sm text-slate-900">{{ $subscription->current_period_end?->format('M d, Y') ?? '—' }}</dd>
                        </div>
                        @if ($subscription->cancel_at_period_end)
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Cancels on</dt>
                                <dd class="text-sm text-amber-600">{{ $subscription->cancel_at?->format('M d, Y') ?? $subscription->current_period_end?->format('M d, Y') ?? '—' }}</dd>
                            </div>
                        @endif
                        @if ($subscription->cancelled_at)
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Cancelled</dt>
                                <dd class="text-sm text-red-600">{{ $subscription->cancelled_at->format('M d, Y') }}</dd>
                            </div>
                        @endif
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Stripe subscription ID</dt>
                            <dd class="flex items-center gap-2 text-sm font-medium">
                                @if ($subscription->stripe_subscription_id)
                                    <span class="font-mono text-slate-900">{{ $subscription->stripe_subscription_id }}</span>
                                    <button
                                        type="button"
                                        x-on:click="navigator.clipboard.writeText('{{ $subscription->stripe_subscription_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                        class="inline-flex items-center rounded p-0.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                                        title="Copy to clipboard"
                                    >
                                        <x-heroicon-o-clipboard-document class="size-3.5" />
                                    </button>
                                @else
                                    <span class="text-slate-500">—</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </x-ui.card>
            </section>

            {{-- Personal Information --}}
            <section id="section-personal" data-section="section-personal">
                <x-ui.card title="Personal information" icon="heroicon-o-user">
                    @if ($subscription->donor)
                        <dl class="space-y-5">
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Name</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ $subscription->donor->name }}</dd>
                            </div>
                            <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                                <dt class="text-sm text-slate-500">Email</dt>
                                <dd class="text-sm font-medium text-slate-900">{{ $subscription->donor->email }}</dd>
                            </div>
                        </dl>
                    @else
                        <p class="text-sm text-slate-500">No supporter linked</p>
                    @endif
                </x-ui.card>
            </section>

            {{-- Source --}}
            <section id="section-source" data-section="section-source">
                <x-ui.card title="Source" icon="heroicon-o-arrow-top-right-on-square">
                    <dl class="space-y-5">
                        <div class="grid grid-cols-1 gap-1 sm:grid-cols-[180px_1fr] sm:gap-6">
                            <dt class="text-sm text-slate-500">Source</dt>
                            <dd class="text-sm font-medium text-slate-900">{{ $subscription->source ? ucfirst(str_replace('_', ' ', $subscription->source)) : 'Checkout Modal' }}</dd>
                        </div>
                    </dl>
                </x-ui.card>
            </section>

            {{-- Recent Payments --}}
            <section id="section-payments" data-section="section-payments">
                <x-ui.card title="Recent payments" icon="heroicon-o-receipt-percent">
                    @if ($this->recentPayments->isNotEmpty())
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-slate-100">
                                        <th class="py-2 pr-4 font-medium text-slate-900">Donation ID</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Amount</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Campaign</th>
                                        <th class="py-2 pr-4 font-medium text-slate-900">Date</th>
                                        <th class="py-2 font-medium text-slate-900">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($this->recentPayments as $payment)
                                        <tr class="border-b border-slate-50 last:border-0">
                                            <td class="py-3 pr-4">
                                                <a href="{{ route('app.donations.show', $payment) }}" wire:navigate class="font-medium text-blue-600 hover:text-blue-700">
                                                    {{ $payment->public_id }}
                                                </a>
                                            </td>
                                            <td class="py-3 pr-4 font-medium text-slate-900">{{ $payment->formatted_amount }}</td>
                                            <td class="py-3 pr-4 text-slate-600">
                                                @if ($payment->campaign)
                                                    {{ $payment->campaign->title }}
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            </td>
                                            <td class="py-3 pr-4 text-slate-600">{{ $payment->created_at->format('M d, Y') }}</td>
                                            <td class="py-3">
                                                <x-ui.badge status="{{ $payment->status->value }}" size="sm">
                                                    {{ ucfirst($payment->status->value === 'succeeded' ? 'Paid' : $payment->status->value) }}
                                                </x-ui.badge>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <x-ui.empty-state
                            icon="heroicon-o-banknotes"
                            title="No payments yet"
                            description="Payments for this subscription will appear here."
                        />
                    @endif
                </x-ui.card>
            </section>
        </div>

        {{-- Right Column / Floating Menu --}}
        <div class="space-y-4">
            <div class="lg:sticky lg:top-6 lg:self-start">
                {{-- Navigation --}}
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5">
                    <nav class="space-y-0.5" aria-label="Subscription sections">
                        <button
                            type="button"
                            @click="scrollToSection('section-subscription')"
                            :class="activeSection === 'section-subscription' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-arrow-path class="size-5" />
                            Subscription
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-billing')"
                            :class="activeSection === 'section-billing' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-credit-card class="size-5" />
                            Billing
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-personal')"
                            :class="activeSection === 'section-personal' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-user class="size-5" />
                            Personal information
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-source')"
                            :class="activeSection === 'section-source' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-arrow-top-right-on-square class="size-5" />
                            Source
                        </button>
                        <button
                            type="button"
                            @click="scrollToSection('section-payments')"
                            :class="activeSection === 'section-payments' ? 'bg-slate-100 text-slate-900' : 'text-slate-600 hover:bg-slate-50'"
                            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-semibold transition"
                        >
                            <x-heroicon-o-receipt-percent class="size-5" />
                            Recent payments
                        </button>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    {{-- Back Link --}}
    <div>
        <a href="{{ route('app.subscriptions.index') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-teal-600 hover:text-teal-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back to subscriptions
        </a>
    </div>
</div>
