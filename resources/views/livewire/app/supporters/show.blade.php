{{-- resources/views/livewire/app/supporters/show.blade.php --}}
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div class="flex items-center gap-4">
            <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-slate-50">
                @if ($donor->photo_url)
                    <img src="{{ $donor->photo_url }}" alt="" class="h-14 w-14 rounded-xl object-cover" />
                @else
                    <x-heroicon-o-user class="size-6 text-slate-400" />
                @endif
            </div>
            <div>
                <h1 class="text-3xl font-bold tracking-tight text-slate-900">{{ $donor->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">Supporter since {{ $donor->created_at->format('M d, Y') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <x-ui.button
                href="/app/virtual-terminal?vt-supporter={{ $donor->public_id }}"
                variant="outline"
                target="_blank"
            >
                <x-heroicon-o-device-phone-mobile class="size-4" />
                View in Virtual Terminal
                <x-heroicon-o-arrow-top-right-on-square class="size-4" />
            </x-ui.button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <x-ui.stat-card
            label="Total Donations"
            value="{{ number_format($this->totalDonationsCount) }}"
        />
        <x-ui.stat-card
            label="Total Amount"
            value="{{ $this->totalAmount }}"
        />
        <x-ui.stat-card
            label="Active Subscriptions"
            value="{{ number_format($this->activeSubscriptionsCount) }}"
        />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left Column --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Recent Donations --}}
            <x-ui.card title="Recent Donations" description="Last 10 donations">
                @if ($this->recentDonations->isNotEmpty())
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
                                @foreach ($this->recentDonations as $donation)
                                    <tr class="transition-colors hover:bg-slate-50">
                                        <td class="px-4 py-3 text-sm text-slate-500">
                                            {{ $donation->created_at->format('M d, Y') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                            {{ $donation->formatted_amount }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            @if ($donation->campaign)
                                                <a href="{{ route('app.campaigns.edit', $donation->campaign) }}" wire:navigate.stop class="hover:text-teal-600">
                                                    {{ $donation->campaign->title }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-ui.badge status="{{ $donation->status->value }}" size="sm">
                                                {{ ucfirst($donation->status->value === 'succeeded' ? 'Paid' : $donation->status->value) }}
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
                        title="No donations yet"
                        description="Donations from this donor will appear here."
                    />
                @endif
            </x-ui.card>

            {{-- Recent Subscriptions --}}
            <x-ui.card title="Recent Subscriptions" description="Last 10 subscriptions">
                @if ($this->recentSubscriptions->isNotEmpty())
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Frequency</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Status</th>
                                    <th scope="col" class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Campaign</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach ($this->recentSubscriptions as $subscription)
                                    <tr class="transition-colors hover:bg-slate-50">
                                        <td class="px-4 py-3 text-sm font-semibold text-slate-900">
                                            {{ $subscription->currency_symbol }} {{ number_format((float) $subscription->amount, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            {{ ucfirst($subscription->interval->value) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            <x-ui.badge status="{{ $subscription->status->value }}" size="sm">
                                                {{ $subscription->status->getLabel() }}
                                            </x-ui.badge>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            @if ($subscription->campaign)
                                                <a href="{{ route('app.campaigns.edit', $subscription->campaign) }}" wire:navigate.stop class="hover:text-teal-600">
                                                    {{ $subscription->campaign->title }}
                                                </a>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <x-ui.empty-state
                        icon="heroicon-o-arrow-path"
                        title="No subscriptions yet"
                        description="Subscriptions from this donor will appear here."
                    />
                @endif
            </x-ui.card>
        </div>

        {{-- Right Column --}}
        <div class="space-y-6">
            {{-- Donor Info --}}
            <x-ui.card title="Donor Information">
                <dl class="space-y-4">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Email</dt>
                        <dd class="mt-0.5 text-sm text-slate-900">{{ $donor->email }}</dd>
                    </div>

                    @if ($donor->phone)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Phone</dt>
                            <dd class="mt-0.5 text-sm text-slate-900">{{ $donor->phone }}</dd>
                        </div>
                    @endif

                    @if ($this->fullAddress)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Address</dt>
                            <dd class="mt-0.5 text-sm text-slate-900">{{ $this->fullAddress }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Donor ID</dt>
                        <dd class="mt-0.5 text-sm text-slate-900 font-mono">{{ $donor->public_id }}</dd>
                    </div>

                    @if ($donor->stripe_customer_id)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Stripe Customer</dt>
                            <dd class="mt-0.5 text-sm text-slate-900 font-mono">{{ $donor->stripe_customer_id }}</dd>
                        </div>
                    @endif
                </dl>
            </x-ui.card>
        </div>
    </div>

    {{-- Back Link --}}
    <div>
        <a href="{{ route('app.supporters.index') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-teal-600 hover:text-teal-700">
            <x-heroicon-o-arrow-left class="size-4 mr-1" />
            Back to donors
        </a>
    </div>
</div>
