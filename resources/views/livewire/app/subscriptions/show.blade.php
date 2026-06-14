{{-- resources/views/livewire/app/subscriptions/show.blade.php --}}
<div class="space-y-6" x-data="{ copied: false }">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold tracking-tight text-slate-900">Subscription Details</h1>
            <p class="mt-1 text-sm text-slate-500">View subscription information and payment history</p>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-ui.stat-card label="Total Payments" value="{{ number_format($this->totalPaymentsCount) }}" />
        <x-ui.stat-card label="Total Paid" value="{{ $this->totalPaidAmount }}" />
        <x-ui.stat-card
            label="Status"
            value="{{ $subscription->status->getLabel() }}"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Recent Payments --}}
            <x-ui.card title="Recent Payments" description="Last 10 payments">
                @if ($this->recentPayments->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campaign</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($this->recentPayments as $payment)
                                    <tr class="transition-colors hover:bg-slate-50">
                                        <td class="px-4 py-3 text-sm text-slate-500">
                                            {{ $payment->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                            {{ $payment->formatted_amount }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            @if ($payment->campaign)
                                                <a href="{{ route('app.campaigns.edit', $payment->campaign) }}" wire:navigate.stop class="hover:text-teal-600">
                                                    {{ $payment->campaign->title }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
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
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Subscription Info --}}
            <x-ui.card title="Subscription Information">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Amount</dt>
                        <dd class="mt-0.5 text-sm text-slate-900 font-semibold">
                            {{ $subscription->currency_symbol }} {{ number_format((float) $subscription->amount, 2) }}
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Currency</dt>
                        <dd class="mt-0.5 text-sm text-slate-900 uppercase">{{ $subscription->currency }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Frequency</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ ucfirst($subscription->interval->value) }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Status</dt>
                        <dd class="mt-0.5">
                            <x-ui.badge status="{{ $subscription->status->value }}" size="sm">
                                {{ $subscription->status->getLabel() }}
                            </x-ui.badge>
                        </dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Stripe Subscription ID</dt>
                        <dd class="mt-0.5 flex items-center gap-2">
                            <span class="text-sm text-slate-900 font-mono">{{ $subscription->stripe_subscription_id ?? '—' }}</span>
                            @if ($subscription->stripe_subscription_id)
                                <button
                                    type="button"
                                    x-on:click="navigator.clipboard.writeText('{{ $subscription->stripe_subscription_id }}'); copied = true; setTimeout(() => copied = false, 2000)"
                                    class="inline-flex items-center text-slate-400 hover:text-teal-600"
                                    title="Copy to clipboard"
                                >
                                    <x-heroicon-o-clipboard-document class="size-4" />
                                </button>
                            @endif
                        </dd>
                        <p x-show="copied" x-transition class="mt-1 text-xs text-teal-600">Copied!</p>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Started</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $subscription->current_period_start?->format('M d, Y') ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Next Billing</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $subscription->current_period_end?->format('M d, Y') ?? '—' }}</dd>
                    </div>

                    @if ($subscription->cancelled_at)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Cancelled</dt>
                            <dd class="mt-0.5 text-sm text-red-600">{{ $subscription->cancelled_at->format('M d, Y') }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Subscription ID</dt>
                        <dd class="mt-0.5 text-sm text-slate-900 font-mono">{{ $subscription->public_id }}</dd>
                    </div>
                </dl>
            </x-ui.card>

            {{-- Donor Card --}}
            <x-ui.card title="Donor">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-slate-50">
                        <x-heroicon-o-user class="size-5 text-slate-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $subscription->donor?->name ?? 'Unknown' }}</p>
                        @if ($subscription->donor?->email)
                            <p class="text-xs text-slate-500">{{ $subscription->donor->email }}</p>
                        @endif
                    </div>
                </div>
                @if ($subscription->donor)
                    <div class="mt-4">
                        <a href="{{ route('app.supporters.show', $subscription->donor) }}" wire:navigate class="inline-flex items-center text-sm font-medium text-teal-600 hover:text-teal-700">
                            View donor profile
                            <x-heroicon-o-arrow-right class="size-4 ml-1" />
                        </a>
                    </div>
                @endif
            </x-ui.card>

            {{-- Campaign Card --}}
            <x-ui.card title="Campaign">
                @if ($subscription->campaign)
                    <p class="text-sm font-medium text-slate-900">{{ $subscription->campaign->title }}</p>
                    <div class="mt-4">
                        <a href="{{ route('app.campaigns.edit', $subscription->campaign) }}" wire:navigate class="inline-flex items-center text-sm font-medium text-teal-600 hover:text-teal-700">
                            View campaign
                            <x-heroicon-o-arrow-right class="size-4 ml-1" />
                        </a>
                    </div>
                @else
                    <p class="text-sm text-slate-400">—</p>
                @endif
            </x-ui.card>
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
